<?php

namespace Friendica\Object\Search;

use Friendica\Model\Search;

/**
 * A search result
 *
 * @see Search for defails
 */
class Result
{
	/**
	 * @var int
	 */
	private $cid;
	/**
	 * @var int
	 */
	private $pcid;
	/**
	 * @var string
	 */
	private $name;
	/**
	 * @var string
	 */
	private $addr;
	/**
	 * @var string
	 */
	private $item;
	/**
	 * @var string
	 */
	private $url;
	/**
	 * @var string
	 */
	private $photo;
	/**
	 * @var string
	 */
	private $tags;
	/**
	 * @var string
	 */
	private $network;

	/**
	 * @return int
	 */
	public function getCid()
	{
		return $this->cid;
	}

	/**
	 * @return int
	 */
	public function getPcid()
	{
		return $this->pcid;
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
	public function getAddr()
	{
		return $this->addr;
	}

	/**
	 * @return string
	 */
	public function getItem()
	{
		return $this->item;
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
	public function getPhoto()
	{
		return $this->photo;
	}

	/**
	 * @return string
	 */
	public function getTags()
	{
		return $this->tags;
	}

	/**
	 * @return string
	 */
	public function getNetwork()
	{
		return $this->network;
	}

	/**
	 * @param string $name
	 * @param string $addr
	 * @param string $url
	 * @param string $photo
	 * @param string $network
	 * @param int    $cid
	 * @param int    $pcid
	 * @param string $tags
	 */
	public function __construct($name, $addr, $item, $url, $photo, $network, $cid = 0, $pcid = 0, $tags = '')
	{
		$this->name    = $name;
		$this->addr    = $addr;
		$this->item    = $item;
		$this->url     = $url;
		$this->photo   = $photo;
		$this->network = $network;

		$this->cid  = $cid;
		$this->pcid = $pcid;
		$this->tags = $tags;
	}
}
