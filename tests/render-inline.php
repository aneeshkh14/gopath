<?php
// Render embedded PHP in inline scripts with deterministic, non-production data.
function admin_url($path){return 'https://portal.example/wp-admin/' . $path;}
function gep_get_url($path){return 'https://portal.example/' . $path;}
function wp_json_encode($value){return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);}
function wp_create_nonce($action){return 'fixture-nonce';}
function esc_js($value){return str_replace(["\\", "'", "\n", "\r"], ["\\\\", "\\'", "\\n", ''], $value);}
class GEP_Category {function get_categories($parent){return [(object)['id'=>1,'name'=>"Sanskrit: उपसर्ग 'quoted'"],(object)['id'=>2,'name'=>'Math & Logic']];}}
$schema_json='{"@context":"https://schema.org","@type":"WebSite","name":"GoPath"}';
$gep_analytics_js=['percentage'=>-5,'correct'=>0,'wrong'=>2,'skipped'=>8,'section_scores'=>[],'topic_data'=>[]];
$attempt_pricing=[['attempts'=>1,'price'=>0],['attempts'=>3,'price'=>100]];
$scripts=[];
foreach(['templates','admin/views','includes'] as $root){
 foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../'.$root,FilesystemIterator::SKIP_DOTS)) as $file){
  if($file->getExtension()!=='php')continue;
  preg_match_all('~<script(\s[^>]*)?>([\s\S]*?)</script>~',file_get_contents($file->getPathname()),$matches,PREG_SET_ORDER);
  foreach($matches as $index=>$match){
   // Shortcode PHP strings can contain concatenation intended for their outer method.
   // They are handled by the regular PHP parser; template scripts render directly.
   if($root==='includes')continue;
   ob_start();eval('?>'.$match[2]);$rendered=ob_get_clean();
   $scripts[]=['file'=>$root.'/'.$file->getFilename().':'.$index,'json'=>strpos($match[1]??'','application/ld+json')!==false,'script'=>$rendered];
  }
 }
}
echo json_encode($scripts,JSON_THROW_ON_ERROR);
