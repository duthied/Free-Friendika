<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Console;

use Friendica\App\BaseURL;
use Friendica\Contact\Avatar;
use Friendica\Core\L10n;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Util\Images;
use Friendica\Object\Image;

/**
 * tool to move cached avatars to the avatar file cache.
 */
class MoveToAvatarCache extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var $dba Friendica\Database\Database
	 */
	private $dba;

	/**
	 * @var $baseurl Friendica\App\BaseURL
	 */
	private $baseurl;

	/**
	 * @var L10n
	 */
	private $l10n;

	protected function getHelp()
	{
		$help = <<<HELP
console movetoavatarcache - Move all cached avatars to the file based avatar cache
Synopsis
	bin/console movetoavatarcache

Description
	bin/console movetoavatarcache
		Move all cached avatars to the file based avatar cache

Options
	-h|--help|-? Show help information
HELP;
		return $help;
	}

	public function __construct(\Friendica\Database\Database $dba, BaseURL $baseurl, L10n $l10n, array $argv = null)
	{
		parent::__construct($argv);

		$this->dba     = $dba;
		$this->baseurl = $baseurl;
		$this->l10n    = $l10n;
	}

	protected function doExecute()
	{
		$condition = ["`avatar` != ? AND `photo` LIKE ? AND `uid` = ? AND `uri-id` != ? AND NOT `uri-id` IS NULL",
			'', $this->baseurl->get() . '/photo/%', 0, 0];

		$count    = 0;
		$total    = $this->dba->count('contact', $condition);
		$contacts = $this->dba->select('contact', ['id', 'avatar', 'photo', 'uri-id', 'url', 'avatar'], $condition, ['order' => ['id']]);
		while ($contact = $this->dba->fetch($contacts)) {
			$this->out(++$count . '/' . $total . "\t" . $contact['id'] . "\t" . $contact['url'] . "\t", false);
			$resourceid = Photo::ridFromURI($contact['photo']);
			if (empty($resourceid)) {
				$this->out($this->l10n->t('no resource in photo %s', $contact['photo']) . ' ', false);
			}

			$this->storeAvatar($resourceid, $contact, false);
		}

		$count  = 0;
		$totals = $this->dba->p("SELECT COUNT(DISTINCT(`resource-id`)) AS `total` FROM `photo` WHERE `contact-id` != ? AND `photo-type` = ?;", 0, Photo::CONTACT_AVATAR);
		$total  = $this->dba->fetch($totals)['total'] ?? 0;
		$photos = $this->dba->p("SELECT `resource-id`, MAX(`contact-id`) AS `contact-id` FROM `photo` WHERE `contact-id` != ? AND `photo-type` = ? GROUP BY `resource-id`;", 0, Photo::CONTACT_AVATAR);
		while ($photo = $this->dba->fetch($photos)) {
			$contact = Contact::getById($photo['contact-id'], ['id', 'avatar', 'photo', 'uri-id', 'url', 'avatar']);
			if (empty($contact)) {
				continue;
			}
			$this->out(++$count . '/' . $total . "\t" . $contact['id'] . "\t" . $contact['url'] . "\t", false);
			$this->storeAvatar($photo['resource-id'], $contact, true);
		}
		return 0;
	}

	private function storeAvatar(string $resourceid, array $contact, bool $quit_on_invalid)
	{
		$valid = !empty($resourceid);
		if ($valid) {
			$this->out('1', false);
			$photo = Photo::selectFirst([], ['resource-id' => $resourceid], ['order' => ['scale']]);
			if (empty($photo)) {
				$this->out(' ' . $this->l10n->t('no photo with id %s', $resourceid) . ' ', false);
				$valid = false;
			}
		}

		if ($valid) {
			$this->out('2', false);
			$imgdata = Photo::getImageDataForPhoto($photo);
			if (empty($imgdata)) {
				$this->out(' ' . $this->l10n->t('no image data for photo with id %s', $resourceid) . ' ', false);
				$valid = false;
			}
		}

		if ($valid) {
			$this->out('3', false);
			$image = new Image($imgdata, Images::getMimeTypeByData($imgdata));
			if (!$image->isValid()) {
				$this->out(' ' . $this->l10n->t('invalid image for id %s', $resourceid) . ' ', false);
				$valid = false;
			}
		}

		if ($valid) {
			$this->out('4', false);
			$fields = Avatar::storeAvatarByImage($contact, $image);
		} else {
			$fields = ['photo' => '', 'thumb' => '', 'micro' => ''];
		}

		if ($quit_on_invalid && $fields['photo'] == '') {
			$this->out(' ' . $this->l10n->t('Quit on invalid photo %s', $contact['avatar']));
			Photo::delete(['resource-id' => $resourceid]);
			return;
		}

		$this->out('5', false);
		Contact::update($fields, ['uri-id' => $contact['uri-id']]);
		$this->out('6', false);
		Photo::delete(['resource-id' => $resourceid]);
		$this->out(' ' . $fields['photo']);
	}
}
