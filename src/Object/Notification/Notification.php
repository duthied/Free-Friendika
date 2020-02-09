<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Object\Notification;

/**
 * A view-only object for printing item notifications to the frontend
 */
class Notification implements \JsonSerializable
{
	const SYSTEM   = 'system';
	const PERSONAL = 'personal';
	const NETWORK  = 'network';
	const INTRO    = 'intro';
	const HOME     = 'home';

	/** @var string */
	private $label = '';
	/** @var string */
	private $link = '';
	/** @var string */
	private $image = '';
	/** @var string */
	private $url = '';
	/** @var string */
	private $text = '';
	/** @var string */
	private $when = '';
	/** @var string */
	private $ago = '';
	/** @var boolean */
	private $seen = false;

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function getLink()
	{
		return $this->link;
	}

	/**
	 * @return string
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getText()
	{
		return $this->text;
	}

	/**
	 * @return string
	 */
	public function getWhen()
	{
		return $this->when;
	}

	/**
	 * @return string
	 */
	public function getAgo()
	{
		return $this->ago;
	}

	/**
	 * @return bool
	 */
	public function isSeen()
	{
		return $this->seen;
	}

	public function __construct(array $data)
	{
		$this->label = $data['label'] ?? '';
		$this->link  = $data['link'] ?? '';
		$this->image = $data['image'] ?? '';
		$this->url   = $data['url'] ?? '';
		$this->text  = $data['text'] ?? '';
		$this->when  = $data['when'] ?? '';
		$this->ago   = $data['ago'] ?? '';
		$this->seen  = $data['seen'] ?? false;
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize()
	{
		return get_object_vars($this);
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return get_object_vars($this);
	}
}
