<?php

namespace Friendica\Protocol;

/**
 * Base class for the Activity namespace
 */
final class Activity
{
	/**
	 * Compare activity uri. Knows about activity namespace.
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return boolean
	 */
	public function match(string $haystack, string $needle) {
		return (($haystack === $needle) ||
		        ((basename($needle) === $haystack) &&
		         strstr($needle, NAMESPACE_ACTIVITY_SCHEMA)));
	}
}
