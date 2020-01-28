<?php

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseEntity;

/**
 * Class Emoji
 *
 * @see https://docs.joinmastodon.org/entities/emoji/
 */
class Emoji extends BaseEntity
{
	//Required attributes
	/** @var string */
	protected $shortcode;
	/** @var string (URL)*/
	protected $static_url;
	/** @var string (URL)*/
	protected $url;
	/**
	 * Unsupported
	 * @var bool
	 */
	protected $visible_in_picker = true;

	// Optional attributes
	/**
	 * Unsupported
	 * @var string
	 */
	//protected $category;

	public function __construct(string $shortcode, string $url)
	{
		$this->shortcode = $shortcode;
		$this->url = $url;
		$this->static_url = $url;
	}

	/**
	 * @param Emoji  $prototype
	 * @param string $shortcode
	 * @param string $url
	 * @return Emoji
	 */
	public static function createFromPrototype(Emoji $prototype, string $shortcode, string $url)
	{
		$emoji = clone $prototype;
		$emoji->shortcode = $shortcode;
		$emoji->url = $url;
		$emoji->static_url = $url;

		return $emoji;
	}
}
