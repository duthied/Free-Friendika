<?php

/**
 * General purpose landing page for plugins/addons
 */

if(! function_exists('cb_init')) {
function cb_init(&$a) {
	call_hooks('cb_init');
}
}

if(! function_exists('cb_post')) {
function cb_post(&$a) {
	call_hooks('cb_post', $_POST);
}
}

if(! function_exists('cb_afterpost')) {
function cb_afterpost(&$a) {
	call_hooks('cb_afterpost');
}
}

if(! function_exists('cb_content')) {
function cb_content(&$a) {
	$o = '';
	call_hooks('cb_content', $o);
	return $o;
}
}
