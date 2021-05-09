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
 * Configuration for the default routes in Friendica
 *
 * The syntax is either
 * - 'route' => [ Module::class , [ HTTPMethod(s) ] ]
 * - 'group' => [ 'route' => [ Module::class, [ HTTPMethod(s) ] ]
 *
 * It's possible to create recursive groups
 *
 */

use Friendica\App\Router as R;
use Friendica\Module;

$profileRoutes = [
	''                                         => [Module\Profile\Index::class,    [R::GET]],
	'/profile'                                 => [Module\Profile\Profile::class,  [R::GET]],
	'/contacts/common'                         => [Module\Profile\Common::class,   [R::GET]],
	'/contacts[/{type}]'                       => [Module\Profile\Contacts::class, [R::GET]],
	'/status[/{category}[/{date1}[/{date2}]]]' => [Module\Profile\Status::class,   [R::GET]],
];

return [
	'/' => [Module\Home::class, [R::GET]],

	'/.well-known' => [
		'/host-meta'      => [Module\WellKnown\HostMeta::class,     [R::GET]],
		'/nodeinfo'       => [Module\WellKnown\NodeInfo::class,     [R::GET]],
		'/security.txt'   => [Module\WellKnown\SecurityTxt::class,  [R::GET]],
		'/webfinger'      => [Module\Xrd::class,                    [R::GET]],
		'/x-nodeinfo2'    => [Module\NodeInfo210::class,            [R::GET]],
		'/x-social-relay' => [Module\WellKnown\XSocialRelay::class, [R::GET]],
	],

	'/2fa' => [
		'[/]'       => [Module\Security\TwoFactor\Verify::class,   [R::GET, R::POST]],
		'/recovery' => [Module\Security\TwoFactor\Recovery::class, [R::GET, R::POST]],
	],

	'/api' => [
		'/v1' => [
			'/accounts'                          => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/accounts/{id:\d+}'                 => [Module\Api\Mastodon\Accounts::class,                 [R::GET         ]],
			'/accounts/{id:\d+}/statuses'        => [Module\Api\Mastodon\Accounts\Statuses::class,        [R::GET         ]],
			'/accounts/{id:\d+}/followers'       => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/accounts/{id:\d+}/following'       => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/accounts/{id:\d+}/lists'           => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/accounts/{id:\d+}/identity_proofs' => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/accounts/{id:\d+}/follow'          => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/accounts/{id:\d+}/unfollow'        => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/accounts/{id:\d+}/block'           => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/accounts/{id:\d+}/unblock'         => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/accounts/{id:\d+}/mute'            => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/accounts/{id:\d+}/unmute'          => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/accounts/{id:\d+}/pin'             => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/accounts/{id:\d+}/unpin'           => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/accounts/{id:\d+}/note'            => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/accounts/relationships'            => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/accounts/search'                   => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/accounts/verify_credentials'       => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/accounts/update_credentials'       => [Module\Api\Mastodon\Unimplemented::class,            [R::PATCH       ]],
			'/admin/accounts'                    => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/admin/accounts/{id:\d+}'           => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/admin/accounts/{id:\d+}/{action}'  => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/admin/reports'                     => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/admin/reports/{id:\d+}'            => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/admin/reports/{id:\d+}/{action}'   => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/announcements'                     => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/announcements/{id:\d+}/dismiss'    => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/announcements/{id:\d+}/reactions/{name}' => [Module\Api\Mastodon\Unimplemented::class,      [R::PUT, R::DELETE]],
			'/apps'                              => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/apps/verify_credentials'           => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/blocks'                            => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/bookmarks'                         => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/conversations'                     => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/conversations/{id:\d+}'            => [Module\Api\Mastodon\Unimplemented::class,            [R::DELETE      ]],
			'/conversations/{id:\d+}/read'       => [Module\Api\Mastodon\Unimplemented::class,            [R::POST        ]],
			'/custom_emojis'                     => [Module\Api\Mastodon\CustomEmojis::class,             [R::GET         ]],
			'/domain_blocks'                     => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::POST, R::DELETE]],
			'/directory'                         => [Module\Api\Mastodon\Directory::class,                [R::GET         ]],
			'/endorsements'                      => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/favourites'                        => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/featured_tags'                     => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::POST]],
			'/featured_tags/{id:\d+}'            => [Module\Api\Mastodon\Unimplemented::class,            [R::DELETE      ]],
			'/featured_tags/suggestions'         => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/filters'                           => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/filters/{id:\d+}'                  => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::POST, R::PUT, R::DELETE]],
			'/follow_requests'                   => [Module\Api\Mastodon\FollowRequests::class,           [R::GET         ]],
			'/follow_requests/{id:\d+}/{action}' => [Module\Api\Mastodon\FollowRequests::class,           [        R::POST]],
			'/instance'                          => [Module\Api\Mastodon\Instance::class,                 [R::GET         ]],
			'/instance/activity'                 => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/instance/peers'                    => [Module\Api\Mastodon\Instance\Peers::class,           [R::GET         ]],
			'/lists'                             => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::POST]],
			'/lists/{id:\d+}'                    => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::PUT, R::DELETE]],
			'/lists/{id:\d+}/accounts'           => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::POST, R::DELETE]],
			'/markers'                           => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::POST]],
			'/media'                             => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/media/{id:\d+}'                    => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::PUT]],
			'/mutes'                             => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/notifications'                     => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/notifications/{id:\d+}'            => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/notifications/clear'               => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/notifications/{id:\d+}/dismiss'    => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/polls/{id:\d+}'                    => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/polls/{id:\d+}/votes'              => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/preferences'                       => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/reports'                           => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/scheduled_statuses'                => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/scheduled_statuses/{id:\d+}'       => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::PUT, R::DELETE]],
			'/statuses'                          => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/statuses/{id:\d+}'                 => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::DELETE]],
			'/statuses/{id:\d+}/context'         => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/statuses/{id:\d+}/reblogged_by'    => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/statuses/{id:\d+}/favourited_by'   => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/statuses/{id:\d+}/favourite'       => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/statuses/{id:\d+}/unfavourite'     => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/statuses/{id:\d+}/reblog'          => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/statuses/{id:\d+}/unreblog'        => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/statuses/{id:\d+}/bookmark'        => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/statuses/{id:\d+}/unbookmark'      => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/statuses/{id:\d+}/mute'            => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/statuses/{id:\d+}/unmute'          => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/statuses/{id:\d+}/pin'             => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/statuses/{id:\d+}/unpin'           => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]],
			'/suggestions'                       => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/suggestions/{id:\d+}'              => [Module\Api\Mastodon\Unimplemented::class,            [R::DELETE      ]],
			'/timelines/home'                    => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/timelines/list/{id:\d+}'           => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/timelines/public'                  => [Module\Api\Mastodon\Timelines\PublicTimeline::class, [R::GET         ]],
			'/timelines/tag/{hashtag}'           => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
			'/trends'                            => [Module\Api\Mastodon\Trends::class,                   [R::GET         ]],
		],
		'/v2' => [
			'/search'                            => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]],
		],
		'/friendica' => [
			'/profile/show'                      => [Module\Api\Friendica\Profile\Show::class , [R::GET         ]],
			'/events'                            => [Module\Api\Friendica\Events\Index::class , [R::GET         ]],
		],
		'/followers/ids'                         => [Module\Api\Twitter\FollowersIds::class   , [R::GET         ]],
		'/followers/list'                        => [Module\Api\Twitter\FollowersList::class  , [R::GET         ]],
		'/friends/ids'                           => [Module\Api\Twitter\FriendsIds::class     , [R::GET         ]],
		'/friends/list'                          => [Module\Api\Twitter\FriendsList::class    , [R::GET         ]],
		'/oembed'                                => [Module\Api\Mastodon\Unimplemented::class,  [R::GET         ]],
	],

	'/admin'               => [
		'[/]' => [Module\Admin\Summary::class, [R::GET]],

		'/addons'         => [Module\Admin\Addons\Index::class,   [R::GET, R::POST]],
		'/addons/{addon}' => [Module\Admin\Addons\Details::class, [R::GET, R::POST]],


		'/blocklist/contact' => [Module\Admin\Blocklist\Contact::class, [R::GET, R::POST]],
		'/blocklist/server'  => [Module\Admin\Blocklist\Server::class,  [R::GET, R::POST]],

		'/dbsync[/{action}[/{update:\d+}]]' => [Module\Admin\DBSync::class, [R::GET]],

		'/features'   => [Module\Admin\Features::class,   [R::GET, R::POST]],
		'/federation' => [Module\Admin\Federation::class, [R::GET]],

		'/item/delete'          => [Module\Admin\Item\Delete::class, [R::GET, R::POST]],
		'/item/source[/{guid}]' => [Module\Admin\Item\Source::class, [R::GET, R::POST]],

		'/logs/view' => [Module\Admin\Logs\View::class,     [R::GET]],
		'/logs'      => [Module\Admin\Logs\Settings::class, [R::GET, R::POST]],

		'/phpinfo' => [Module\Admin\PhpInfo::class, [R::GET]],

		'/queue[/{status}]' => [Module\Admin\Queue::class, [R::GET]],

		'/site' => [Module\Admin\Site::class, [R::GET, R::POST]],

		'/themes'               => [Module\Admin\Themes\Index::class,   [R::GET, R::POST]],
		'/themes/{theme}'       => [Module\Admin\Themes\Details::class, [R::GET, R::POST]],
		'/themes/{theme}/embed' => [Module\Admin\Themes\Embed::class,   [R::GET, R::POST]],

		'/tos' => [Module\Admin\Tos::class, [R::GET, R::POST]],

		'/users[/{action}/{uid}]'         => [Module\Admin\Users\Index::class,   [R::GET, R::POST]],
		'/users/active[/{action}/{uid}]'  => [Module\Admin\Users\Active::class,  [R::GET, R::POST]],
		'/users/pending[/{action}/{uid}]' => [Module\Admin\Users\Pending::class, [R::GET, R::POST]],
		'/users/blocked[/{action}/{uid}]' => [Module\Admin\Users\Blocked::class, [R::GET, R::POST]],
		'/users/deleted'                  => [Module\Admin\Users\Deleted::class, [R::GET         ]],
		'/users/create'                   => [Module\Admin\Users\Create::class,  [R::GET, R::POST]],
	],
	'/amcd'                => [Module\AccountManagementControlDocument::class, [R::GET]],
	'/acctlink'            => [Module\Acctlink::class,     [R::GET]],
	'/apps'                => [Module\Apps::class,         [R::GET]],
	'/attach/{item:\d+}'   => [Module\Attach::class,       [R::GET]],
	'/babel'               => [Module\Debug\Babel::class,  [R::GET, R::POST]],
	'/debug/ap'            => [Module\Debug\ActivityPubConversion::class,  [R::GET, R::POST]],
	'/bookmarklet'         => [Module\Bookmarklet::class,  [R::GET]],

	'/community[/{content}]' => [Module\Conversation\Community::class, [R::GET]],

	'/compose[/{type}]'    => [Module\Item\Compose::class, [R::GET, R::POST]],

	'/contact'   => [
		'[/]'                         => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}[/]'                => [Module\Contact::class,           [R::GET, R::POST]],
		'/{id:\d+}/archive'           => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/advanced'          => [Module\Contact\Advanced::class,  [R::GET, R::POST]],
		'/{id:\d+}/block'             => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/conversations'     => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/contacts[/{type}]' => [Module\Contact\Contacts::class,  [R::GET]],
		'/{id:\d+}/drop'              => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/ignore'            => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/poke'              => [Module\Contact\Poke::class,      [R::GET, R::POST]],
		'/{id:\d+}/posts'             => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/update'            => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/updateprofile'     => [Module\Contact::class,           [R::GET]],
		'/archived'                   => [Module\Contact::class,           [R::GET]],
		'/batch'                      => [Module\Contact::class,           [R::GET, R::POST]],
		'/pending'                    => [Module\Contact::class,           [R::GET]],
		'/blocked'                    => [Module\Contact::class,           [R::GET]],
		'/hidden'                     => [Module\Contact::class,           [R::GET]],
		'/ignored'                    => [Module\Contact::class,           [R::GET]],
		'/hovercard'                  => [Module\Contact\Hovercard::class, [R::GET]],
	],

	'/credits'               => [Module\Credits::class,        [R::GET]],

	'/delegation'=> [Module\Delegation::class,       [R::GET, R::POST]],
	'/dirfind'   => [Module\Search\Directory::class, [R::GET]],
	'/directory' => [Module\Directory::class,        [R::GET]],

	'/feed'     => [
		'/{nickname}'          => [Module\Feed::class, [R::GET]],
		'/{nickname}/posts'    => [Module\Feed::class, [R::GET]],
		'/{nickname}/comments' => [Module\Feed::class, [R::GET]],
		'/{nickname}/replies'  => [Module\Feed::class, [R::GET]],
		'/{nickname}/activity' => [Module\Feed::class, [R::GET]],
	],
	'/feedtest' => [Module\Debug\Feed::class, [R::GET]],

	'/fetch'             => [
		'/post/{guid}'           => [Module\Diaspora\Fetch::class, [R::GET]],
		'/status_message/{guid}' => [Module\Diaspora\Fetch::class, [R::GET]],
		'/reshare/{guid}'        => [Module\Diaspora\Fetch::class, [R::GET]],
	],
	'/filed'             => [Module\Search\Filed::class,    [R::GET]],
	'/filer[/{id:\d+}]'  => [Module\Filer\SaveTag::class,   [R::GET]],
	'/filerm/{id:\d+}'   => [Module\Filer\RemoveTag::class, [R::GET]],
	'/follow_confirm'    => [Module\FollowConfirm::class,   [R::GET, R::POST]],
	'/followers/{owner}' => [Module\Followers::class,       [R::GET]],
	'/following/{owner}' => [Module\Following::class,       [R::GET]],
	'/friendica[/json]'  => [Module\Friendica::class,       [R::GET]],
	'/friendica/inbox'   => [Module\Inbox::class,           [R::GET, R::POST]],

	'/fsuggest/{contact:\d+}' => [Module\FriendSuggest::class,  [R::GET, R::POST]],

	'/group'              => [
		'[/]'                        => [Module\Group::class, [R::GET, R::POST]],
		'/{group:\d+}'               => [Module\Group::class, [R::GET, R::POST]],
		'/none'                      => [Module\Group::class, [R::GET, R::POST]],
		'/new'                       => [Module\Group::class, [R::GET, R::POST]],
		'/drop/{group:\d+}'          => [Module\Group::class, [R::GET, R::POST]],
		'/{group:\d+}/{contact:\d+}' => [Module\Group::class, [R::GET, R::POST]],

		'/{group:\d+}/add/{contact:\d+}'    => [Module\Group::class, [R::GET, R::POST]],
		'/{group:\d+}/remove/{contact:\d+}' => [Module\Group::class, [R::GET, R::POST]],
	],
	'/hashtag'                    => [Module\Hashtag::class,   [R::GET]],
	'/help[/{doc:.+}]'            => [Module\Help::class,      [R::GET]],
	'/home'                       => [Module\Home::class,      [R::GET]],
	'/hcard/{profile}[/{action}]' => [Module\HoverCard::class, [R::GET]],
	'/inbox[/{nickname}]'         => [Module\Inbox::class,     [R::GET, R::POST]],
	'/invite'                     => [Module\Invite::class,    [R::GET, R::POST]],

	'/install'         => [
		'[/]'                    => [Module\Install::class, [R::GET, R::POST]],
		'/testrewrite'           => [Module\Install::class, [R::GET]],
	],

	'/item/{id:\d+}'            => [
		'/activity/{verb}' => [Module\Item\Activity::class,    [        R::POST]],
		'/follow'          => [Module\Item\Follow::class,      [        R::POST]],
		'/ignore'          => [Module\Item\Ignore::class,      [        R::POST]],
		'/pin'             => [Module\Item\Pin::class,         [        R::POST]],
		'/star'            => [Module\Item\Star::class,        [        R::POST]],
	],

	'/localtime'          => [Module\Debug\Localtime::class, [R::GET, R::POST]],
	'/login'              => [Module\Security\Login::class,  [R::GET, R::POST]],
	'/logout'             => [Module\Security\Logout::class, [R::GET, R::POST]],
	'/magic'              => [Module\Magic::class,           [R::GET]],
	'/manifest'           => [Module\Manifest::class,        [R::GET]],
	'/friendica.webmanifest'  => [Module\Manifest::class,    [R::GET]],
	'/modexp/{nick}'      => [Module\PublicRSAKey::class,    [R::GET]],
	'/newmember'          => [Module\Welcome::class,         [R::GET]],
	'/nodeinfo/1.0'       => [Module\NodeInfo110::class,     [R::GET]],
	'/nodeinfo/2.0'       => [Module\NodeInfo120::class,     [R::GET]],
	'/nogroup'            => [Module\Group::class,           [R::GET]],

	'/noscrape' => [
		'/{nick}'         => [Module\NoScrape::class, [R::GET]],
		'/{profile}/view' => [Module\NoScrape::class, [R::GET]],
	],

	'/notifications' => [
		'/network[/json]'    => [Module\Notifications\Notifications::class, [R::GET, R::POST]],
		'/system[/json]'     => [Module\Notifications\Notifications::class, [R::GET, R::POST]],
		'/personal[/json]'   => [Module\Notifications\Notifications::class, [R::GET, R::POST]],
		'/home[/json]'       => [Module\Notifications\Notifications::class, [R::GET, R::POST]],
		'/intros[/json]'     => [Module\Notifications\Introductions::class, [R::GET, R::POST]],
		'/intros/all[/json]' => [Module\Notifications\Introductions::class, [R::GET, R::POST]],
		'/intros/{contact:\d+}[/json]' => [Module\Notifications\Introductions::class, [R::GET, R::POST]],
	],

	'/notification'         => [
		'[/]'       => [Module\Notifications\Notification::class, [R::GET]],
		'/mark/all' => [Module\Notifications\Notification::class, [R::GET]],
		'/{id:\d+}' => [Module\Notifications\Notification::class, [R::GET, R::POST]],
	],
	'/oauth/authorize'             => [Module\Api\Mastodon\Unimplemented::class, [R::GET]],
	'/oauth/revoke'                => [Module\Api\Mastodon\Unimplemented::class, [R::POST]],
	'/oauth/token'                 => [Module\Api\Mastodon\Unimplemented::class, [R::POST]],
	'/objects/{guid}[/{activity}]' => [Module\Objects::class, [R::GET]],

	'/oembed'         => [
		'/b2h'    => [Module\Oembed::class, [R::GET]],
		'/h2b'    => [Module\Oembed::class, [R::GET]],
		'/{hash}' => [Module\Oembed::class, [R::GET]],
	],
	'/outbox/{owner}' => [Module\Outbox::class,          [R::GET]],
	'/owa'            => [Module\Owa::class,             [R::GET]],
	'/openid'         => [Module\Security\OpenID::class, [R::GET]],
	'/opensearch'     => [Module\OpenSearch::class,      [R::GET]],

	'/parseurl'                           => [Module\ParseUrl::class,          [R::GET]],
	'/permission/tooltip/{type}/{id:\d+}' => [Module\PermissionTooltip::class, [R::GET]],

	'/photo' => [
		'/{name}'                    => [Module\Photo::class, [R::GET]],
		'/{type}/{name}'             => [Module\Photo::class, [R::GET]],
		'/{type}/{customize}/{name}' => [Module\Photo::class, [R::GET]],
	],

	'/pretheme'          => [Module\ThemeDetails::class, [R::GET]],
	'/probe'             => [Module\Debug\Probe::class,  [R::GET]],

	'/proofs'            => [Module\Api\Mastodon\Unimplemented::class, [R::GET]],

	'/profile/{nickname}' => $profileRoutes,
	'/u/{nickname}'       => $profileRoutes,
	'/~{nickname}'        => $profileRoutes,

	'/proxy' => [
		'[/]'                  => [Module\Proxy::class, [R::GET]],
		'/{url}'               => [Module\Proxy::class, [R::GET]],
		'/{sub1}/{url}'        => [Module\Proxy::class, [R::GET]],
		'/{sub1}/{sub2}/{url}' => [Module\Proxy::class, [R::GET]],
	],

	'/search' => [
		'[/]'                  => [Module\Search\Index::class, [R::GET]],
		'/acl'                 => [Module\Search\Acl::class,   [R::GET, R::POST]],
		'/saved/add'           => [Module\Search\Saved::class, [R::GET]],
		'/saved/remove'        => [Module\Search\Saved::class, [R::GET]],
	],

	'/receive' => [
		'/public'       => [Module\Diaspora\Receive::class, [R::POST]],
		'/users/{guid}' => [Module\Diaspora\Receive::class, [R::POST]],
	],

	'/settings' => [
		'/2fa' => [
			'[/]'           => [Module\Settings\TwoFactor\Index::class,       [R::GET, R::POST]],
			'/recovery'     => [Module\Settings\TwoFactor\Recovery::class,    [R::GET, R::POST]],
			'/app_specific' => [Module\Settings\TwoFactor\AppSpecific::class, [R::GET, R::POST]],
			'/verify'       => [Module\Settings\TwoFactor\Verify::class,      [R::GET, R::POST]],
			'/trusted'      => [Module\Settings\TwoFactor\Trusted::class, [R::GET, R::POST]],
		],
		'/delegation[/{action}/{user_id}]' => [Module\Settings\Delegation::class,       [R::GET, R::POST]],
		'/display'                 => [Module\Settings\Display::class,             [R::GET, R::POST]],
		'/profile' => [
			'[/]'                  => [Module\Settings\Profile\Index::class,       [R::GET, R::POST]],
			'/photo[/new]'         => [Module\Settings\Profile\Photo\Index::class, [R::GET, R::POST]],
			'/photo/crop/{guid}'   => [Module\Settings\Profile\Photo\Crop::class,  [R::GET, R::POST]],
		],
		'/userexport[/{action}]' => [Module\Settings\UserExport::class,             [R::GET, R::POST]],
	],

	'/network' => [
		'[/]'                         => [Module\Conversation\Network::class, [R::GET]],
		'/archive/{from:\d\d\d\d-\d\d-\d\d}[/{to:\d\d\d\d-\d\d-\d\d}]' => [Module\Conversation\Network::class, [R::GET]],
		'/forum/{contact_id:\d+}'     => [Module\Conversation\Network::class, [R::GET]],
		'/group/{group_id:\d+}'       => [Module\Conversation\Network::class, [R::GET]],
	],

	'/randprof'                      => [Module\RandomProfile::class,         [R::GET]],
	'/register'                      => [Module\Register::class,              [R::GET, R::POST]],
	'/remote_follow/{profile}'       => [Module\RemoteFollow::class,          [R::GET, R::POST]],
	'/robots.txt'                    => [Module\RobotsTxt::class,             [R::GET]],
	'/rsd.xml'                       => [Module\ReallySimpleDiscovery::class, [R::GET]],
	'/smilies[/json]'                => [Module\Smilies::class,               [R::GET]],
	'/statistics.json'               => [Module\Statistics::class,            [R::GET]],
	'/toggle_mobile'                 => [Module\ToggleMobile::class,          [R::GET]],
	'/tos'                           => [Module\Tos::class,                   [R::GET]],

	'/update_community[/{content}]'  => [Module\Update\Community::class,      [R::GET]],
	'/update_network'                => [Module\Update\Network::class,        [R::GET]],
	'/update_profile'                => [Module\Update\Profile::class,        [R::GET]],

	'/view/theme/{theme}/style.pcss' => [Module\Theme::class,                 [R::GET]],
	'/viewsrc/{item:\d+}'            => [Module\Debug\ItemBody::class,        [R::GET]],
	'/webfinger'                     => [Module\Debug\WebFinger::class,       [R::GET]],
	'/xrd'                           => [Module\Xrd::class,                   [R::GET]],
	'/worker'                        => [Module\Worker::class,                [R::GET]],
];
