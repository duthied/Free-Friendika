<?php

namespace Friendica\Test\Util;

use Friendica\Util\Emailer;

class EmailerSpy extends Emailer
{
	public static $MAIL_DATA;

	protected function mail(string $to, string $subject, string $body, string $headers, string $parameters)
	{
		self::$MAIL_DATA = [
			'to' => $to,
			'subject' => $subject,
			'body' => $body,
			'headers' => $headers,
			'parameters' => $parameters,
		];

		return true;
	}
}
