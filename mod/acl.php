<?php
/* ACL selector json backend */

require_once("include/acl_selectors.php");

if(! function_exists('acl_init')) {
function acl_init(&$a){
	acl_lookup($a);
}
}
