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
 * tool to control the list of ActivityPub relay servers from the CLI
 *
 * With this script you can access the relay servers of your node from
 * the CLI.
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
		$total = $this->dba->count('contact', $condition);
		$contacts = $this->dba->select('contact', ['id', 'avatar', 'photo', 'uri-id', 'url', 'avatar'], $condition, ['order' => ['id' => true]]);
		$count = 0;
		while ($contact = $this->dba->fetch($contacts)) {
			echo ++$count . '/' . $total . "\t" . $contact['id'] . "\t" . $contact['url'] . "\t";
			$resourceid = Photo::ridFromURI($contact['photo']);
			if (empty($resourceid)) {
					echo $this->l10n->t('no resource') . "\n";
					continue;
			}
			echo '1';
			$photo = Photo::selectFirst([], ['resource-id' => $resourceid], ['order' => ['scale']]);
			if (empty($photo)) {
				echo $this->l10n->t('no photo') . "\n";
				continue;
			}
					
			echo '2';
			$imgdata = Photo::getImageDataForPhoto($photo);
			if (empty($imgdata)) {
					echo $this->l10n->t('no image data') . "\n";
					continue;
			}
			echo '3';
			$image = new Image($imgdata, Images::getMimeTypeByData($imgdata));
			if (!$image->isValid()) {
					echo $this->l10n->t('invalid image') . "\n";
					continue;
			}
	
			echo '4';
			$fields = Avatar::storeAvatarByImage($contact, $image);
			echo '5';
			Contact::update($fields, ['uri-id' => $contact['uri-id']]);
			echo '6';
			Photo::delete(['resource-id' => $resourceid]);
			echo ' '.$fields['photo'] . "\n";
		}
				
		return 0;
	}
}
