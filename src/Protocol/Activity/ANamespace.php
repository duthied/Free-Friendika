<?php

namespace Friendica\Protocol\Activity;

/**
 * Activity namespaces constants
 */
final class ANamespace
{
	const ZOT             = 'http://purl.org/zot';
	const DFRN            = 'http://purl.org/macgirvin/dfrn/1.0';
	const THREAD          = 'http://purl.org/syndication/thread/1.0';
	const TOMB            = 'http://purl.org/atompub/tombstones/1.0';
	const ACTIVITY2       = 'https://www.w3.org/ns/activitystreams#';
	const ACTIVITY        = 'http://activitystrea.ms/spec/1.0/';
	const ACTIVITY_SCHEMA = 'http://activitystrea.ms/schema/1.0/';
	const MEDIA           = 'http://purl.org/syndication/atommedia';
	const SALMON_ME       = 'http://salmon-protocol.org/ns/magic-env';
	const OSTATUSSUB      = 'http://ostatus.org/schema/1.0/subscribe';
	const GEORSS          = 'http://www.georss.org/georss';
	const POCO            = 'http://portablecontacts.net/spec/1.0';
	const FEED            = 'http://schemas.google.com/g/2010#updates-from';
	const OSTATUS         = 'http://ostatus.org/schema/1.0';
	const STATUSNET       = 'http://status.net/schema/api/1/';
	const ATOM1           = 'http://www.w3.org/2005/Atom';
	const MASTODON        = 'http://mastodon.social/schema/1.0';
}
