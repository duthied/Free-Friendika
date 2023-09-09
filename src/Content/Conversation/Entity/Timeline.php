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

namespace Friendica\Content\Conversation\Entity;

/**
 * @property-read string $code        Channel code
 * @property-read string $label       Channel label
 * @property-read string $description Channel description
 * @property-read string $accessKey   Access key
 * @property-read string $path        Path
 */
final class Timeline extends \Friendica\BaseEntity
{
	const WHATSHOT         = 'whatshot';
	const FORYOU           = 'foryou';
	const FOLLOWERS        = 'followers';
	const SHARERSOFSHARERS = 'sharersofsharers';
	const IMAGE            = 'image';
	const VIDEO            = 'video';
	const AUDIO            = 'audio';
	const LANGUAGE         = 'language';
	const LOCAL            = 'local';
	const GLOBAL           = 'global';
	const STAR             = 'star';
	const MENTION          = 'mention';
	const RECEIVED         = 'received';
	const COMMENTED        = 'commented';
	const CREATED          = 'created';

	/** @var string */
	protected $code;
	/** @var string */
	protected $label;
	/** @var string */
	protected $description;
	/** @var string */
	protected $accessKey;
	/** @var string */
	protected $path;

	public function __construct(string $code, string $label, string $description, string $accessKey, string $path = null)
	{
		$this->code        = $code;
		$this->label       = $label;
		$this->description = $description;
		$this->accessKey   = $accessKey;
		$this->path        = $path;
	}
}
