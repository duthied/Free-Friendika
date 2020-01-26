<?php

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
