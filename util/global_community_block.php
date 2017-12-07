#!/usr/bin/env php
<?php
/**
 * @brief tool to block an account from the node
 *
 * With this tool, you can block an account in such a way, that no postings
 * or comments this account writes are accepted to the node.
 *
 * Usage: pass the URL of the to be blocked account as only parameter
 *        at the command line when running this tool. E.g.
 *
 *        $> util/global_community_block.php http://example.com/profile/bob
 *
 *        will block bob@example.com.
 *
 * Author: Tobias Diekershoff
 *
 * License: AGPLv3 or later, same as Friendica
 */
if ($argc != 2 || $argv[1] == "-h" || $argv[1] == "--help" || $argv[1] == "-?") {
	echo "Usage: " . $argv[0] . " [-h|profile_url]\r\n";
	echo "    -h, -?, --help ... show this help\r\n";
	echo "    profile_url ...... The URL of the profile you want to silence\r\n";
	echo "\r\n";
	echo "Example: block bob@example.com\r\n";
	echo "$> " . $argv[0] . " https://example.com/profiles/bob\r\n";
	echo "\r\n";
	exit(0);
}

use Friendica\BaseObject;
use Friendica\Model\Contact;

require_once 'boot.php';
require_once 'include/dba.php';
require_once 'include/text.php';

$a = get_app();;
BaseObject::setApp($a);

require_once '.htconfig.php';
dba::connect($db_host, $db_user, $db_pass, $db_data);
unset($db_host, $db_user, $db_pass, $db_data);

$contact_id = Contact::getIdForURL($argv[1], 0);
if (!$contact_id) {
	echo t('Could not find any contact entry for this URL (%s)', $nurl);
	echo "\r\n";
	exit(1);
}
Contact::block($contact_id);
echo t('The contact has been blocked from the node');
echo "\r\n";
exit(0);
