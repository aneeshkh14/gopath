<?php
// Run only against the disposable gep_test schema created by CI.
require __DIR__.'/php-fixtures.php';
$dsn=getenv('GEP_TEST_MYSQL_DSN');
if (!$dsn || strpos($dsn,'dbname=gep_test')===false) {fwrite(STDERR,"A disposable gep_test MySQL database is required.\n");exit(1);}
class IntegrationDb {
 public $prefix='wp_', $insert_id=0, $fail_order_update=false; public $pdo;
 function __construct(){ $this->pdo=new PDO(getenv('GEP_TEST_MYSQL_DSN'),'root','fixture',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); }
 function prepare($query,...$args){$index=0;return preg_replace_callback('/%[dsf]/',function($m)use($args,&$index){$value=$args[$index++];return $m[0]==='%s'?$this->pdo->quote((string)$value):(string)(float)$value;},$query);}
 function query($query){try{return $this->pdo->exec($query);}catch(PDOException $e){return false;}}
 function get_row($query){return $this->pdo->query($query)->fetch(PDO::FETCH_OBJ) ?: null;}
 function get_var($query){$value=$this->pdo->query($query)->fetchColumn();return $value===false?null:$value;}
 function insert($table,$data){$fields=implode(',',array_keys($data));$marks=implode(',',array_fill(0,count($data),'?'));try{$q=$this->pdo->prepare("INSERT INTO $table ($fields) VALUES ($marks)");$q->execute(array_values($data));$this->insert_id=(int)$this->pdo->lastInsertId();return $q->rowCount();}catch(PDOException $e){$this->insert_id=0;return false;}}
 function update($table,$data,$where,...$unused){if($this->fail_order_update && $table==='wp_gep_orders')return false;$set=implode(',',array_map(fn($k)=>"$k = ?",array_keys($data)));$clause=implode(' AND ',array_map(fn($k)=>"$k = ?",array_keys($where)));try{$q=$this->pdo->prepare("UPDATE $table SET $set WHERE $clause");$q->execute(array_merge(array_values($data),array_values($where)));return $q->rowCount();}catch(PDOException $e){return false;}}
}
$uid=7;$options=['gep_razorpay_key_secret'=>base64_encode('test-secret')];$wpdb=new IntegrationDb();
$schema=[
 'wp_gep_orders'=>'id BIGINT PRIMARY KEY AUTO_INCREMENT,user_id BIGINT,item_id BIGINT,item_type VARCHAR(20),amount DECIMAL(10,2),discount DECIMAL(10,2),coupon_code VARCHAR(50),status VARCHAR(20),attempts INT,created_at DATETIME,razorpay_order_id VARCHAR(100) UNIQUE,razorpay_payment_id VARCHAR(100)',
 'wp_gep_tests'=>'id BIGINT PRIMARY KEY,type VARCHAR(20),price DECIMAL(10,2),translated_data TEXT',
 'wp_gep_user_test_access'=>'id BIGINT PRIMARY KEY AUTO_INCREMENT,user_id BIGINT,test_id BIGINT,extra_attempts INT DEFAULT 0,assigned_at DATETIME,UNIQUE(user_id,test_id)',
 'wp_gep_coupons'=>'id BIGINT PRIMARY KEY AUTO_INCREMENT,code VARCHAR(50),used_count INT DEFAULT 0'
];
foreach($schema as $table=>$columns){expect($wpdb->query("CREATE TABLE IF NOT EXISTS $table ($columns) ENGINE=InnoDB")!==false);}
function seed(){global $wpdb,$schema;foreach($schema as $table=>$unused)$wpdb->query("TRUNCATE TABLE $table");$wpdb->query("INSERT INTO wp_gep_tests VALUES(2,'random',100,'{}'),(3,'mock',0,'{}'),(4,'mock',100,'{}')");$wpdb->query("INSERT INTO wp_gep_user_test_access(user_id,test_id,extra_attempts) VALUES(7,2,5)");$wpdb->query("INSERT INTO wp_gep_orders(user_id,item_id,item_type,status,attempts,razorpay_order_id,coupon_code) VALUES(7,2,'test','pending',3,'order_test','SAVE')");$wpdb->query("INSERT INTO wp_gep_coupons(code,used_count) VALUES('SAVE',0)");}
function verify(){return (new GEP_Payment())->verify_payment('order_test','pay_test',hash_hmac('sha256','order_test|pay_test','test-secret'),2,'test');}
seed();expect(verify());expect(verify());expect((int)$wpdb->get_var('SELECT extra_attempts FROM wp_gep_user_test_access WHERE test_id=2')===8);expect((int)$wpdb->get_var('SELECT used_count FROM wp_gep_coupons')===1);echo "PASS MySQL replay grants once and counts coupon once\n";
seed();$wpdb->fail_order_update=true;expect(!verify());expect((int)$wpdb->get_var('SELECT extra_attempts FROM wp_gep_user_test_access WHERE test_id=2')===5);expect($wpdb->get_var('SELECT status FROM wp_gep_orders')==='pending');$wpdb->fail_order_update=false;expect(verify());expect((int)$wpdb->get_var('SELECT extra_attempts FROM wp_gep_user_test_access WHERE test_id=2')===8);echo "PASS MySQL failed order recording rolls back access and remains retryable\n";
seed();$free=(new GEP_Payment())->create_order(3,'test');expect(!is_wp_error($free) && $free['status']==='free');expect($wpdb->get_var('SELECT status FROM wp_gep_orders WHERE item_id=3')==='success');expect((int)$wpdb->get_var('SELECT COUNT(*) FROM wp_gep_user_test_access WHERE test_id=3')===1);class FreeCouponPaymentFixture extends GEP_Payment { public function validate_coupon($code,$item_id,$item_type='test',$attempts=0){return ['discount'=>100,'new_total'=>0];} }
$discounted=(new FreeCouponPaymentFixture())->create_order(4,'test','SAVE');expect(!is_wp_error($discounted) && $discounted['status']==='free');expect((int)$wpdb->get_var('SELECT used_count FROM wp_gep_coupons')===1);expect((int)$wpdb->get_var('SELECT COUNT(*) FROM wp_gep_user_test_access WHERE test_id=4')===1);
echo "PASS MySQL free enrollment and full-discount coupon grant access and record usage\n";
seed();if(!function_exists('pcntl_fork'))throw new Exception('pcntl is required for concurrent callback coverage');$wpdb=null;$children=[];
for($i=0;$i<2;$i++){ $pid=pcntl_fork();if($pid===-1)throw new Exception('Could not fork');if($pid===0){$wpdb=new IntegrationDb();exit(verify()?0:1);} $children[]=$pid; }
foreach($children as $pid){pcntl_waitpid($pid,$status);expect(pcntl_wexitstatus($status)===0,'Concurrent callback failed');}
$wpdb=new IntegrationDb();expect((int)$wpdb->get_var('SELECT extra_attempts FROM wp_gep_user_test_access WHERE test_id=2')===8);expect((int)$wpdb->get_var('SELECT used_count FROM wp_gep_coupons')===1);echo "PASS MySQL simultaneous callbacks grant once\n";
echo "4 MySQL integration scenarios passed.\n";
