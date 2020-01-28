<?php

namespace Friendica\Factory\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

class Account extends BaseFactory
{
	/** @var BaseURL */
	protected $baseUrl;

	public function __construct(LoggerInterface $logger, BaseURL $baseURL)
	{
		parent::__construct($logger);

		$this->baseUrl = $baseURL;
	}

	/**
	 * @param int $contactId
	 * @param int $uid        User Id
	 * @return \Friendica\Object\Api\Mastodon\Account
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createFromContactId(int $contactId, $uid = 0)
	{
		$cdata = Contact::getPublicAndUserContacID($contactId, $uid);
		if (!empty($cdata)) {
			$publicContact = Contact::getById($cdata['public']);
			$userContact = Contact::getById($cdata['user']);
		} else {
			$publicContact = Contact::getById($contactId);
			$userContact = [];
		}

		$apcontact = APContact::getByURL($publicContact['url'], false);

		return new \Friendica\Object\Api\Mastodon\Account($this->baseUrl, $publicContact, $apcontact, $userContact);
	}
}
