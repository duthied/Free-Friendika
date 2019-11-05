<?php

namespace Friendica\Module;

use Friendica\BaseModule;

/**
 * Static definition for the Firefox Account Manager
 *
 * @see https://wiki.mozilla.org/Labs/Weave/Identity/Account_Manager/Spec/3#Contents_of_the_Account_Management_Control_Document
 */
class AccountManagementControlDocument extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$output = [
			'version' => 1,
			'sessionstatus' => [
				'method' => 'GET',
				'path' => '/session',
			],
			'auth-methods' => [
				'username-password-form' => [
					'connect' => [
						'method' => 'POST',
						'path' => '/login',
						'params' => [
							'username' => 'login-name',
							'password' => 'password',
						],
						'onsuccess' => [
							'action' => 'reload',
						],
					],
					'disconnect' => [
						'method' => 'GET',
						'path' => '/logout',
					],
				],
			],
			'methods' => [
				'username-password-form' => [
					'connect' => [
						'method' => 'POST',
						'path' => '/login',
						'params' => [
							'username' => 'login-name',
							'password' => 'password',
						],
						'onsuccess' => [
							'action' => 'reload',
						],
					],
					'disconnect' => [
						'method' => 'GET',
						'path' => '/logout',
					],
				],
			],
		];

		echo json_encode($output);
		exit();
	}
}
