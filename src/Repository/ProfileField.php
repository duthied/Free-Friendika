<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Repository;

use Friendica\BaseRepository;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model;
use Friendica\Security\PermissionSet\Depository\PermissionSet;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class ProfileField extends BaseRepository
{
	protected static $table_name = 'profile_field';

	protected static $model_class = \Friendica\Profile\ProfileField\Entity\ProfileField::class;

	protected static $collection_class = \Friendica\Profile\ProfileField\Collection\ProfileFields::class;

	/** @var PermissionSet */
	private $permissionSet;
	/** @var \Friendica\Security\PermissionSet\Factory\PermissionSet */
	private $permissionSetFactory;
	/** @var L10n */
	private $l10n;

	public function __construct(Database $dba, LoggerInterface $logger, PermissionSet $permissionSet, \Friendica\Security\PermissionSet\Factory\PermissionSet $permissionSetFactory, L10n $l10n)
	{
		parent::__construct($dba, $logger);

		$this->permissionSet        = $permissionSet;
		$this->permissionSetFactory = $permissionSetFactory;
		$this->l10n                 = $l10n;
	}

	/**
	 * @param array $fields
	 *
	 * @return \Friendica\Profile\ProfileField\Entity\ProfileField|bool
	 * @throws \Exception
	 */
	public function insert(array $fields)
	{
		$fields['created'] = DateTimeFormat::utcNow();
		$fields['edited']  = DateTimeFormat::utcNow();

		return parent::insert($fields);
	}

	/**
	 * Migrates a legacy profile to the new slimmer profile with extra custom fields.
	 * Multi profiles are converted to ACl-protected custom fields and deleted.
	 *
	 * @param array $profile Profile table row
	 * @throws \Exception
	 */
	public function migrateFromLegacyProfile(array $profile)
	{
		// Already processed, aborting
		if ($profile['is-default'] === null) {
			return;
		}

		$contacts = [];

		if (!$profile['is-default']) {
			$contacts = Model\Contact::selectToArray(['id'], ['uid' => $profile['uid'], 'profile-id' => $profile['id']]);
			if (!count($contacts)) {
				// No contact visibility selected defaults to user-only permission
				$contacts = Model\Contact::selectToArray(['id'], ['uid' => $profile['uid'], 'self' => true]);
			}
		}

		$psid = $this->permissionSet->selectOrCreate(
			new \Friendica\Security\PermissionSet\Entity\PermissionSet(
				$profile['uid'],
				array_column($contacts, 'id') ?? []
			)
		)->id;

		$order = 1;

		$custom_fields = [
			'hometown'  => $this->l10n->t('Hometown:'),
			'marital'   => $this->l10n->t('Marital Status:'),
			'with'      => $this->l10n->t('With:'),
			'howlong'   => $this->l10n->t('Since:'),
			'sexual'    => $this->l10n->t('Sexual Preference:'),
			'politic'   => $this->l10n->t('Political Views:'),
			'religion'  => $this->l10n->t('Religious Views:'),
			'likes'     => $this->l10n->t('Likes:'),
			'dislikes'  => $this->l10n->t('Dislikes:'),
			'pdesc'     => $this->l10n->t('Title/Description:'),
			'summary'   => $this->l10n->t('Summary'),
			'music'     => $this->l10n->t('Musical interests'),
			'book'      => $this->l10n->t('Books, literature'),
			'tv'        => $this->l10n->t('Television'),
			'film'      => $this->l10n->t('Film/dance/culture/entertainment'),
			'interest'  => $this->l10n->t('Hobbies/Interests'),
			'romance'   => $this->l10n->t('Love/romance'),
			'work'      => $this->l10n->t('Work/employment'),
			'education' => $this->l10n->t('School/education'),
			'contact'   => $this->l10n->t('Contact information and Social Networks'),
		];

		foreach ($custom_fields as $field => $label) {
			if (!empty($profile[$field]) && $profile[$field] > DBA::NULL_DATE && $profile[$field] > DBA::NULL_DATETIME) {
				$this->insert([
					'uid'   => $profile['uid'],
					'psid'  => $psid,
					'order' => $order++,
					'label' => trim($label, ':'),
					'value' => $profile[$field],
				]);
			}

			$profile[$field] = null;
		}

		if ($profile['is-default']) {
			$profile['profile-name'] = null;
			$profile['is-default']   = null;
			$this->dba->update('profile', $profile, ['id' => $profile['id']]);
		} elseif (!empty($profile['id'])) {
			$this->dba->delete('profile', ['id' => $profile['id']]);
		}
	}
}
