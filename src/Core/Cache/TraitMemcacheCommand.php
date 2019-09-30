<?php

namespace Friendica\Core\Cache;

use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * Trait for Memcache to add a custom version of the
 * method getAllKeys() since this isn't working anymore
 *
 * Adds the possibility to directly communicate with the memcache too
 */
trait TraitMemcacheCommand
{
	/**
	 * @var string server address
	 */
	protected $server;

	/**
	 * @var int server port
	 */
	protected $port;

	/**
	 * Retrieves the stored keys of the memcache instance
	 * Uses custom commands, which aren't bound to the used instance of the class
	 *
	 * @todo Due the fact that we use a custom command, there are race conditions possible:
	 *       - $this->memcache(d) adds a key
	 *       - $this->getMemcacheKeys is called directly "after"
	 *       - But $this->memcache(d) isn't finished adding the key, so getMemcacheKeys doesn't find it
	 *
	 * @return array All keys of the memcache instance
	 *
	 * @throws InternalServerErrorException
	 */
	protected function getMemcacheKeys()
	{
		$string = $this->sendMemcacheCommand("stats items");
		$lines  = explode("\r\n", $string);
		$slabs  = [];
		$keys   = [];

		foreach ($lines as $line) {

			if (preg_match("/STAT items:([\d]+):number ([\d]+)/", $line, $matches) &&
			    isset($matches[1]) &&
			    !in_array($matches[1], $keys)) {

				$slabs[] = $matches[1];
				$string  = $this->sendMemcacheCommand("stats cachedump " . $matches[1] . " " . $matches[2]);
				preg_match_all("/ITEM (.*?) /", $string, $matches);
				$keys = array_merge($keys, $matches[1]);
			}
		}

		return $keys;
	}

	/**
	 * Taken directly from memcache PECL source
	 * Sends a command to the memcache instance and returns the result
	 * as a string
	 *
	 * http://pecl.php.net/package/memcache
	 *
	 * @param string $command The command to send to the Memcache server
	 *
	 * @return string The returned buffer result
	 *
	 * @throws InternalServerErrorException In case the memcache server isn't available (anymore)
	 */
	protected function sendMemcacheCommand(string $command)
	{
		$s = @fsockopen($this->server, $this->port);
		if (!$s) {
			throw new InternalServerErrorException("Cant connect to:" . $this->server . ':' . $this->port);
		}

		fwrite($s, $command . "\r\n");
		$buf = '';

		while (!feof($s)) {

			$buf .= fgets($s, 256);

			if (strpos($buf, "END\r\n") !== false) { // stat says end
				break;
			}

			if (strpos($buf, "DELETED\r\n") !== false || strpos($buf, "NOT_FOUND\r\n") !== false) { // delete says these
				break;
			}

			if (strpos($buf, "OK\r\n") !== false) { // flush_all says ok
				break;
			}
		}

		fclose($s);
		return ($buf);
	}
}
