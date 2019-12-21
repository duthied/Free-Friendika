<?php
/**
 * @file src/Worker/UpdateSuggestions.php
 */
namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Model\GContact;

class UpdateSuggestions
{
	/**
	 * Discover other servers for their contacts.
	 */
	public static function execute()
	{
		GContact::updateSuggestions();
	}
}
