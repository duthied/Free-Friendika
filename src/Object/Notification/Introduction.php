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
 * A view-only object for printing introduction notifications to the frontend
 */
class Introduction implements \JsonSerializable
{
	/** @var string */
	private $label = '';
	/** @var string */
	private $type = '';
	/** @var integer */
	private $intro_id = -1;
	/** @var string */
	private $madeBy = '';
	/** @var string */
	private $madeByUrl = '';
	/** @var string */
	private $madeByZrl = '';
	/** @var string */
	private $madeByAddr = '';
	/** @var integer */
	private $contactId = -1;
	/** @var string */
	private $photo = '';
	/** @var string */
	private $name = '';
	/** @var string */
	private $url = '';
	/** @var string */
	private $zrl = '';
	/** @var boolean */
	private $hidden = false;
	/** @var integer */
	private $postNewFriend = -1;
	/** @var boolean */
	private $knowYou = false;
	/** @var string */
	private $note = '';
	/** @var string */
	private $request = '';
	/** @var int */
	private $dfrnId = -1;
	/** @var string */
	private $addr = '';
	/** @var string */
	private $network = '';
	/** @var int */
	private $uid = -1;
	/** @var string */
	private $keywords = '';
	/** @var string */
	private $location = '';
	/** @var string */
	private $about = '';

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
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function getIntroId()
	{
		return $this->intro_id;
	}

	/**
	 * @return string
	 */
	public function getMadeBy()
	{
		return $this->madeBy;
	}

	/**
	 * @return string
	 */
	public function getMadeByUrl()
	{
		return $this->madeByUrl;
	}

	/**
	 * @return string
	 */
	public function getMadeByZrl()
	{
		return $this->madeByZrl;
	}

	/**
	 * @return string
	 */
	public function getMadeByAddr()
	{
		return $this->madeByAddr;
	}

	/**
	 * @return int
	 */
	public function getContactId()
	{
		return $this->contactId;
	}

	/**
	 * @return string
	 */
	public function getPhoto()
	{
		return $this->photo;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
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
	public function getZrl()
	{
		return $this->zrl;
	}

	/**
	 * @return bool
	 */
	public function isHidden()
	{
		return $this->hidden;
	}

	/**
	 * @return int
	 */
	public function getPostNewFriend()
	{
		return $this->postNewFriend;
	}

	/**
	 * @return string
	 */
	public function getKnowYou()
	{
		return $this->knowYou;
	}

	/**
	 * @return string
	 */
	public function getNote()
	{
		return $this->note;
	}

	/**
	 * @return string
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @return string
	 */
	public function getDfrnId()
	{
		return $this->dfrnId;
	}

	/**
	 * @return string
	 */
	public function getAddr()
	{
		return $this->addr;
	}

	/**
	 * @return string
	 */
	public function getNetwork()
	{
		return $this->network;
	}

	/**
	 * @return int
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @return string
	 */
	public function getKeywords()
	{
		return $this->keywords;
	}

	/**
	 * @return string
	 */
	public function getLocation()
	{
		return $this->location;
	}

	/**
	 * @return string
	 */
	public function getAbout()
	{
		return $this->about;
	}

	public function __construct(array $data = [])
	{
		$this->label         = $data['label'] ?? '';
		$this->type          = $data['str_type'] ?? '';
		$this->intro_id      = $data['intro_id'] ?? -1;
		$this->madeBy        = $data['madeBy'] ?? '';
		$this->madeByUrl     = $data['madeByUrl'] ?? '';
		$this->madeByZrl     = $data['madeByZrl'] ?? '';
		$this->madeByAddr    = $data['madeByAddr'] ?? '';
		$this->contactId     = $data['contactId'] ?? '';
		$this->photo         = $data['photo'] ?? '';
		$this->name          = $data['name'] ?? '';
		$this->url           = $data['url'] ?? '';
		$this->zrl           = $data['zrl'] ?? '';
		$this->hidden        = $data['hidden'] ?? false;
		$this->postNewFriend = $data['postNewFriend'] ?? '';
		$this->knowYou       = $data['knowYou'] ?? false;
		$this->note          = $data['note'] ?? '';
		$this->request       = $data['request'] ?? '';
		$this->dfrnId        = $data['dfrn_id'] ?? -1;
		$this->addr          = $data['addr'] ?? '';
		$this->network       = $data['network'] ?? '';
		$this->uid           = $data['uid'] ?? -1;
		$this->keywords      = $data['keywords'] ?? '';
		$this->location      = $data['location'] ?? '';
		$this->about         = $data['about'] ?? '';
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return get_object_vars($this);
	}
}
