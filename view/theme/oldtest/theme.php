<?php
/**
 * Name: Test
 * Description: Test theme
 * 
 */
 
 require_once 'object/TemplateEngine.php';
 
 
 function test_init(&$a){
 	#$a->theme_info = array();
	$a->register_template_engine("JSONIficator");
	$a->set_template_engine('jsonificator');
 }


 
 class JSONIficator implements ITemplateEngine {
 	static $name = 'jsonificator';
	
	public $data = array();
	private $last_template;
	
	public function replace_macros($s,$v){
		$dbg = debug_backtrace();
		$cntx = $dbg[2]['function'];
		if ($cntx=="") {
			$cntx=basename($dbg[1]['file']);
		}
		if (!isset($this->data[$cntx])) {
			$this->data[$cntx] = array();
		}
		$nv = array();
		foreach ($v as $key => $value) {
			$nkey = $key;
			if ($key[0]==="$") $nkey = substr($key, 1);
			$nv[$nkey] = $value;
		}
		$this->data[$cntx][] = $nv;
	}
	public function get_template_file($file, $root=''){
		return  "";
	}
	
 }
