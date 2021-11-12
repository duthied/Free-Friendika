<?php

namespace Friendica\Test\Util;

use Friendica\Security\Authentication;

class AuthenticationDouble extends Authentication
{
	protected function setXAccMgmtStatusHeader(array $user_record)
	{
		// Don't set any header..
	}
}
