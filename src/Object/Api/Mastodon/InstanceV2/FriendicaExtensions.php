<?php

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\App;
use Friendica\BaseDataTransferObject;
use Friendica\DI;

/**
 * Class FriendicaExtensions
 *
 * Friendica specific additional fields on the Instance V2 object
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class FriendicaExtensions extends BaseDataTransferObject
{
	/** @var string */
	protected $version;
	/** @var string */
	protected $codename;
	/** @var int */
	protected $db_version;

	public function __construct()
	{
		$this->version    = App::VERSION;
		$this->codename   = App::CODENAME;
		$this->db_version = DI::config()->get('system', 'build');
	}
}
