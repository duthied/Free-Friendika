<?php

if(! function_exists('home_init')) {
function home_init(&$a) {

	$ret = array();
	call_hooks('home_init',$ret);

	if(local_user() && ($a->user['nickname']))
		goaway( $a->get_baseurl() . "/profile/" . $a->user['nickname'] );

	if(strlen(get_config('system','singleuser')))
		goaway( $a->get_baseurl() . "/profile/" . get_config('system','singleuser'));

}}


if(! function_exists('home_content')) {
function home_content(&$a) {

	$o = '';

	if(x($_SESSION,'theme'))
		unset($_SESSION['theme']);
	if(x($_SESSION,'mobile-theme'))
		unset($_SESSION['mobile-theme']);

	if(file_exists('home.html')){
		if(file_exists('home.css')){
			  $a->page['htmlhead'] .= '<link rel="stylesheet" type="text/css" href="' . $a->get_baseurl() . '/home.css' . '" media="all" />';}
					  
 		$o .= file_get_contents('home.html');}
 		
	else 	$o .= '<h1>' . ((x($a->config,'sitename')) ? sprintf( t("Welcome to %s") ,$a->config['sitename']) : "" ) . '</h1>';


	$o .= login(($a->config['register_policy'] == REGISTER_CLOSED) ? 0 : 1);
	
	call_hooks("home_content",$o);
	
	return $o;

	
}} 
