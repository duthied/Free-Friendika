<?php

namespace Friendica\Factory\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Collection\Api\Mastodon\Fields;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Friendica\Repository\PermissionSet;
use Friendica\Repository\ProfileField;
use Psr\Log\LoggerInterface;

class Account extends BaseFactory
{
	/** @var BaseURL */
	protected $baseUrl;
	/** @var ProfileField */
	protected $profileField;
	/** @var Field */
	protected $mstdnField;

	public function __construct(LoggerInterface $logger, BaseURL $baseURL, ProfileField $profileField, Field $mstdnField)
	{
		parent::__construct($logger);

		$this->baseUrl = $baseURL;
		$this->profileField = $profileField;
		$this->mstdnField = $mstdnField;
	}

	/**
	 * @param int $contactId
	 * @param int $uid        Public contact (=0) or owner user id
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

		return new \Friendica\Object\Api\Mastodon\Account($this->baseUrl, $publicContact, new Fields(), $apcontact, $userContact);
	}

	/**
	 * @param int $userId
	 * @return \Friendica\Object\Api\Mastodon\Account
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createFromUserId(int $userId)
	{
		$publicContact = Contact::selectFirst([], ['uid' => $userId, 'self' => true]);

		$profileFields = $this->profileField->select(['uid' => $userId, 'psid' => PermissionSet::PUBLIC]);
		$fields        = $this->mstdnField->createFromProfileFields($profileFields);

		$apcontact     = APContact::getByURL($publicContact['url'], false);

		return new \Friendica\Object\Api\Mastodon\Account($this->baseUrl, $publicContact, $fields, $apcontact);
	}
}
