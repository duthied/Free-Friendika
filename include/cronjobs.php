<?php
use \Friendica\Core\Config;

function cronjobs_run(&$argv, &$argc){
	global $a;

	require_once('include/datetime.php');
	require_once('include/ostatus.php');
	require_once('include/post_update.php');
	require_once('mod/nodeinfo.php');

	// No parameter set? So return
	if ($argc <= 1)
		return;

	// Check OStatus conversations
	// Check only conversations with mentions (for a longer time)
	if ($argv[1] == 'ostatus_mentions') {
		ostatus::check_conversations(true);
		return;
	}

	// Check every conversation
	if ($argv[1] == 'ostatus_conversations') {
		ostatus::check_conversations(false);
		return;
	}

	// Call possible post update functions
	// see include/post_update.php for more details
	if ($argv[1] == 'post_update') {
		post_update();
		return;
	}

	// update nodeinfo data
	if ($argv[1] == 'nodeinfo') {
		nodeinfo_cron();
		return;
	}

	return;
}
