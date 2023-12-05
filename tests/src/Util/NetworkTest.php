<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Util;

use Friendica\Util\Network;
use PHPUnit\Framework\TestCase;

/**
 * Network utility test class
 */
class NetworkTest extends TestCase
{
	public function testValidUri()
	{
		self::assertNotNull(Network::createUriFromString('https://friendi.ca'));
		self::assertNotNull(Network::createUriFromString('magnet:?xs=https%3A%2F%2Ftube.jeena.net%2Flazy-static%2Ftorrents%2F04bec7a8-34de-4847-b080-6ee00c4b3d49-1080-hls.torrent&xt=urn:btih:5def5a24dfa7307e999a0d4f0fcc29c3e2b13be2&dn=My+fediverse+setup+-+I+host+everything+myself&tr=https%3A%2F%2Ftube.jeena.net%2Ftracker%2Fannounce&tr=wss%3A%2F%2Ftube.jeena.net%3A443%2Ftracker%2Fsocket&ws=https%3A%2F%2Ftube.jeena.net%2Fstatic%2Fstreaming-playlists%2Fhls%2F23989f41-e230-4dbf-9111-936bc730bf50%2Fe5905de3-e488-4bb8-a1e8-eb7a53ac24ad-1080-fragmented.mp4'));
		self::assertNotNull(Network::createUriFromString('did:plc:geqiabvo4b4jnfv2paplzcge'));
		self::assertNull(Network::createUriFromString('https://'));
		self::assertNull(Network::createUriFromString(''));
		self::assertNull(Network::createUriFromString(null));
	}
}
