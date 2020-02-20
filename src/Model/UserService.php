<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU APGL version 3 or any later version
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

namespace Friendica\Model;

use ErrorException;
use Friendica\App;
use Friendica\Core\Config\IConfig;
use Friendica\Core\L10n;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\Emailer;
use Friendica\Util\Strings;
use Friendica\Model\User as UserModel;
use ImagickException;

class UserService
{
	/** @var L10n */
	private $l10n;
	/** @var IConfig */
	private $config;
	/** @var App\BaseURL */
	private $baseUrl;
	/** @var Emailer */
	private $emailer;

	public function __construct(L10n $l10n, IConfig $config, Emailer $emailer, App\BaseURL $baseUrl)
	{
		$this->l10n = $l10n;
		$this->config = $config;
		$this->emailer = $emailer;
		$this->baseUrl = $baseUrl;
	}

	/**
	 * Creates a new user based on a minimal set and sends an email to this user
	 *
	 * @param string $name The user's name
	 * @param string $email The user's email address
	 * @param string $nick The user's nick name
	 * @param string $lang The user's language (default is english)
	 *
	 * @return bool True, if the user was created successfully
	 * @throws InternalServerErrorException
	 * @throws ErrorException
	 * @throws ImagickException
	 */
	public function createMinimal(string $name, string $email, string $nick, string $lang = L10n::DEFAULT)
	{
		if (empty($name) ||
		    empty($email) ||
		    empty($nick)) {
			throw new InternalServerErrorException('Invalid arguments.');
		}

		$result = UserModel::create([
			'username' => $name,
			'email' => $email,
			'nickname' => $nick,
			'verified' => 1,
			'language' => $lang
		]);

		$user = $result['user'];
		$preamble = Strings::deindent($this->l10n->t('
		Dear %1$s,
			the administrator of %2$s has set up an account for you.'));
		$body = Strings::deindent($this->l10n->t('
		The login details are as follows:

		Site Location:	%1$s
		Login Name:		%2$s
		Password:		%3$s

		You may change your password from your account "Settings" page after logging
		in.

		Please take a few moments to review the other account settings on that page.

		You may also wish to add some basic information to your default profile
		(on the "Profiles" page) so that other people can easily find you.

		We recommend setting your full name, adding a profile photo,
		adding some profile "keywords" (very useful in making new friends) - and
		perhaps what country you live in; if you do not wish to be more specific
		than that.

		We fully respect your right to privacy, and none of these items are necessary.
		If you are new and do not know anybody here, they may help
		you to make some new and interesting friends.

		If you ever want to delete your account, you can do so at %1$s/removeme

		Thank you and welcome to %4$s.'));

		$preamble = sprintf($preamble, $user['username'], $this->config->get('config', 'sitename'));
		$body = sprintf($body, $this->baseUrl->get(), $user['nickname'], $result['password'], $this->config->get('config', 'sitename'));

		$email = $this->emailer
		           ->newSystemMail()
		           ->withMessage($this->l10n->t('Registration details for %s', $this->config->get('config', 'sitename')), $preamble, $body)
		           ->forUser($user)
		           ->withRecipient($user['email'])
		           ->build();
		return $this->emailer->send($email);
	}
}
