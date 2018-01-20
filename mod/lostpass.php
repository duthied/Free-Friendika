<?php

/**
 * @file mod/lostpass.php
 */

use Friendica\App;
use Friendica\Core\System;
use Friendica\Database\DBM;

require_once 'include/boot.php';
require_once 'include/enotify.php';
require_once 'include/text.php';
require_once 'include/pgettext.php';

function lostpass_post(App $a)
{
	$loginame = notags(trim($_POST['login-name']));
	if (!$loginame) {
		goaway(System::baseUrl());
	}

	$condition = ['(`email` = ? OR `nickname` = ?) AND `verified` = 1 AND `blocked` = 0', $loginame, $loginame];
	$user = dba::selectFirst('user', ['uid', 'username', 'email'], $condition);
	if (!DBM::is_result($user)) {
		notice(t('No valid account found.') . EOL);
		goaway(System::baseUrl());
	}

	$pwdreset_token = autoname(12) . mt_rand(1000, 9999);

	$result = dba::update('user', ['pwdreset' => $pwdreset_token], ['uid' => $user['uid']]);
	if ($result) {
		info(t('Password reset request issued. Check your email.') . EOL);
	}

	$sitename = $a->config['sitename'];
	$resetlink = System::baseUrl() . '/lostpass?verify=' . $pwdreset_token;

	$preamble = deindent(t('
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email.

		Your password will not be changed unless we can verify that you
		issued this request.', $user['username'], $sitename));
	$body = deindent(t('
		Follow this link to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s', $resetlink, System::baseUrl(), $user['email']));

	notification([
		'type'     => SYSTEM_EMAIL,
		'to_email' => $user['email'],
		'subject'  => t('Password reset requested at %s', $sitename),
		'preamble' => $preamble,
		'body'     => $body
	]);

	goaway(System::baseUrl());
}

function lostpass_content(App $a)
{
	$o = '';
	if (x($_GET, 'verify')) {
		$pwdreset_token = $_GET['verify'];

		$user = dba::selectFirst('user', ['uid', 'username', 'email'], ['pwdreset' => $pwdreset_token]);
		if (!DBM::is_result($user)) {
			$o = t("Request could not be verified. \x28You may have previously submitted it.\x29 Password reset failed.");
			return $o;
		}

		$new_password = autoname(6) . mt_rand(100, 9999);
		$new_password_encoded = hash('whirlpool', $new_password);

		$result = dba::update('user', ['password' => $new_password_encoded, 'pwdreset' => ''], ['uid' => $user['uid']]);
		if (DBM::is_result($result)) {
			$tpl = get_markup_template('pwdreset.tpl');
			$o .= replace_macros($tpl,
				[
				'$lbl1'    => t('Password Reset'),
				'$lbl2'    => t('Your password has been reset as requested.'),
				'$lbl3'    => t('Your new password is'),
				'$lbl4'    => t('Save or copy your new password - and then'),
				'$lbl5'    => '<a href="' . System::baseUrl() . '">' . t('click here to login') . '</a>.',
				'$lbl6'    => t('Your password may be changed from the <em>Settings</em> page after successful login.'),
				'$newpass' => $new_password,
				'$baseurl' => System::baseUrl()
			]);

			info("Your password has been reset." . EOL);

			$sitename = $a->config['sitename'];
			$preamble = deindent(t('
				Dear %1$s,
					Your password has been changed as requested. Please retain this
				information for your records (or change your password immediately to
				something that you will remember).
			', $user['username']));
			$body = deindent(t('
				Your login details are as follows:

				Site Location:	%1$s
				Login Name:	%2$s
				Password:	%3$s

				You may change that password from your account settings page after logging in.
			', System::baseUrl(), $user['email'], $new_password));

			notification([
				'type'     => SYSTEM_EMAIL,
				'to_email' => $user['email'],
				'subject'  => t('Your password has been changed at %s', $sitename),
				'preamble' => $preamble,
				'body'     => $body
			]);

			return $o;
		}
	} else {
		$tpl = get_markup_template('lostpass.tpl');
		$o .= replace_macros($tpl, [
			'$title'  => t('Forgot your Password?'),
			'$desc'   => t('Enter your email address and submit to have your password reset. Then check your email for further instructions.'),
			'$name'   => t('Nickname or Email: '),
			'$submit' => t('Reset')
		]);

		return $o;
	}
}
