<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
 * Main view structure configuration file.
 *
 * Here are described all the view Friendica needs to work.
 *
 * Syntax (braces indicate optionale values):
 * "<view name>" => [
 *	"fields" => [
 *		"<field name>" => ["table", "field"],
 *		"<field name>" => "SQL expression",
 *		...
 *	],
 *	"query" => "FROM `table` INNER JOIN `other-table` ..."
 *	],
 * ],
 *
 * If you need to make any change, make sure to increment the DB_UPDATE_VERSION constant value in dbstructure.config.php.
 *
 */

return [
	"category-view" => [
		"fields" => [
			"uri-id" => ["post-category", "uri-id"],
			"uid" => ["post-category", "uid"],
			"uri" => ["item-uri", "uri"],
			"guid" => ["item-uri", "guid"],
			"type" => ["post-category", "type"],
			"tid" => ["post-category", "tid"],
			"name" => ["tag", "name"],
			"url" => ["tag", "url"],
		],
		"query" => "FROM `post-category`
			INNER JOIN `item-uri` ON `item-uri`.id = `post-category`.`uri-id`
			LEFT JOIN `tag` ON `post-category`.`tid` = `tag`.`id`"
	],
	"tag-view" => [
		"fields" => [
			"uri-id" => ["post-tag", "uri-id"],
			"uri" => ["item-uri", "uri"],
			"guid" => ["item-uri", "guid"],
			"type" => ["post-tag", "type"],
			"tid" => ["post-tag", "tid"],
			"cid" => ["post-tag", "cid"],
			"name" => "CASE `cid` WHEN 0 THEN `tag`.`name` ELSE `contact`.`name` END",
			"url" => "CASE `cid` WHEN 0 THEN `tag`.`url` ELSE `contact`.`url` END",
		],
		"query" => "FROM `post-tag`
			INNER JOIN `item-uri` ON `item-uri`.id = `post-tag`.`uri-id`
			LEFT JOIN `tag` ON `post-tag`.`tid` = `tag`.`id`
			LEFT JOIN `contact` ON `post-tag`.`cid` = `contact`.`id`"
	],
	"owner-view" => [
		"fields" => [
			"id" => ["contact", "id"],
			"uid" => ["contact", "uid"],
			"created" => ["contact", "created"],
			"updated" => ["contact", "updated"],
			"self" => ["contact", "self"],
			"remote_self" => ["contact", "remote_self"],
			"rel" => ["contact", "rel"],
			"duplex" => ["contact", "duplex"],
			"network" => ["contact", "network"],
			"protocol" => ["contact", "protocol"],
			"name" => ["contact", "name"],
			"nick" => ["contact", "nick"],
			"location" => ["contact", "location"],
			"about" => ["contact", "about"],
			"keywords" => ["contact", "keywords"],
			"gender" => ["contact", "gender"],
			"xmpp" => ["contact", "xmpp"],
			"attag" => ["contact", "attag"],
			"avatar" => ["contact", "avatar"],
			"photo" => ["contact", "photo"],
			"thumb" => ["contact", "thumb"],
			"micro" => ["contact", "micro"],
			"site-pubkey" => ["contact", "site-pubkey"],
			"issued-id" => ["contact", "issued-id"],
			"dfrn-id" => ["contact", "dfrn-id"],
			"url" => ["contact", "url"],
			"nurl" => ["contact", "nurl"],
			"addr" => ["contact", "addr"],
			"alias" => ["contact", "alias"],
			"pubkey" => ["contact", "pubkey"],
			"prvkey" => ["contact", "prvkey"],
			"batch" => ["contact", "batch"],
			"request" => ["contact", "request"],
			"notify" => ["contact", "notify"],
			"poll" => ["contact", "poll"],
			"confirm" => ["contact", "confirm"],
			"poco" => ["contact", "poco"],
			"aes_allow" => ["contact", "aes_allow"],
			"ret-aes" => ["contact", "ret-aes"],
			"usehub" => ["contact", "usehub"],
			"subhub" => ["contact", "subhub"],
			"hub-verify" => ["contact", "hub-verify"],
			"last-update" => ["contact", "last-update"],
			"success_update" => ["contact", "success_update"],
			"failure_update" => ["contact", "failure_update"],
			"name-date" => ["contact", "name-date"],
			"uri-date" => ["contact", "uri-date"],
			"avatar-date" => ["contact", "avatar-date"],
			"picdate" => ["contact", "avatar-date"], /// @todo Replaces all uses of "picdate" with "avatar-date"
			"term-date" => ["contact", "term-date"],
			"last-item" => ["contact", "last-item"],
			"priority" => ["contact", "priority"],
			"blocked" => ["contact", "blocked"], /// @todo Check if "blocked" from contact or from the users table
			"block_reason" => ["contact", "block_reason"],
			"readonly" => ["contact", "readonly"],
			"writable" => ["contact", "writable"],
			"forum" => ["contact", "forum"],
			"prv" => ["contact", "prv"],
			"contact-type" => ["contact", "contact-type"],
			"hidden" => ["contact", "hidden"],
			"archive" => ["contact", "archive"],
			"pending" => ["contact", "pending"],
			"deleted" => ["contact", "deleted"],
			"rating" => ["contact", "rating"],
			"unsearchable" => ["contact", "unsearchable"],
			"sensitive" => ["contact", "sensitive"],
			"baseurl" => ["contact", "baseurl"],
			"reason" => ["contact", "reason"],
			"closeness" => ["contact", "closeness"],
			"info" => ["contact", "info"],
			"profile-id" => ["contact", "profile-id"],
			"bdyear" => ["contact", "bdyear"],
			"bd" => ["contact", "bd"],
			"notify_new_posts" => ["contact", "notify_new_posts"],
			"fetch_further_information" => ["contact", "fetch_further_information"],
			"ffi_keyword_denylist" => ["contact", "ffi_keyword_denylist"],
			"parent-uid" => ["user", "parent-uid"],
			"guid" => ["user", "guid"],
			"nickname" => ["user", "nickname"], /// @todo Replaces all uses of "nickname" with "nick"
			"email" => ["user", "email"],
			"openid" => ["user", "openid"],
			"timezone" => ["user", "timezone"],
			"language" => ["user", "language"],
			"register_date" => ["user", "register_date"],
			"login_date" => ["user", "login_date"],
			"default-location" => ["user", "default-location"],
			"allow_location" => ["user", "allow_location"],
			"theme" => ["user", "theme"],
			"upubkey" => ["user", "pubkey"],
			"uprvkey" => ["user", "prvkey"],
			"sprvkey" => ["user", "sprvkey"],
			"spubkey" => ["user", "spubkey"],
			"verified" => ["user", "verified"],
			"blockwall" => ["user", "blockwall"],
			"hidewall" => ["user", "hidewall"],
			"blocktags" => ["user", "blocktags"],
			"unkmail" => ["user", "unkmail"],
			"cntunkmail" => ["user", "cntunkmail"],
			"notify-flags" => ["user", "notify-flags"],
			"page-flags" => ["user", "page-flags"],
			"account-type" => ["user", "account-type"],
			"prvnets" => ["user", "prvnets"],
			"maxreq" => ["user", "maxreq"],
			"expire" => ["user", "expire"],
			"account_removed" => ["user", "account_removed"],
			"account_expired" => ["user", "account_expired"],
			"account_expires_on" => ["user", "account_expires_on"],
			"expire_notification_sent" => ["user", "expire_notification_sent"],			
			"def_gid" => ["user", "def_gid"],
			"allow_cid" => ["user", "allow_cid"],
			"allow_gid" => ["user", "allow_gid"],
			"deny_cid" => ["user", "deny_cid"],
			"deny_gid" => ["user", "deny_gid"],
			"openidserver" => ["user", "openidserver"],
			"publish" => ["profile", "publish"],
			"net-publish" => ["profile", "net-publish"],
			"hide-friends" => ["profile", "hide-friends"],
			"prv_keywords" => ["profile", "prv_keywords"],
			"pub_keywords" => ["profile", "pub_keywords"],
			"address" => ["profile", "address"],
			"locality" => ["profile", "locality"],
			"region" => ["profile", "region"],
			"postal-code" => ["profile", "postal-code"],
			"country-name" => ["profile", "country-name"],
			"homepage" => ["profile", "homepage"],
			"dob" => ["profile", "dob"],
		],
		"query" => "FROM `user`
			INNER JOIN `contact` ON `contact`.`uid` = `user`.`uid` AND `contact`.`self`
			INNER JOIN `profile` ON `profile`.`uid` = `user`.`uid`"
	],
	"pending-view" => [
		"fields" => [
			"id" => ["register", "id"],
			"hash" => ["register", "hash"],
			"created" => ["register", "created"],
			"uid" => ["register", "uid"],
			"password" => ["register", "password"],
			"language" => ["register", "language"],
			"note" => ["register", "note"],
			"self" => ["contact", "self"],
			"name" => ["contact", "name"],
			"url" => ["contact", "url"],
			"micro" => ["contact", "micro"],
			"email" => ["user", "email"],
			"nick" => ["contact", "nick"],
		],
		"query" => "FROM `register`
			INNER JOIN `contact` ON `register`.`uid` = `contact`.`uid`
			INNER JOIN `user` ON `register`.`uid` = `user`.`uid`"
	],
	"tag-search-view" => [
		"fields" => [
			"uri-id" => ["post-tag", "uri-id"],
			"iid" => ["item", "id"],
			"uri" => ["item", "uri"],
			"guid" => ["item", "guid"],
			"uid" => ["item", "uid"],
			"private" => ["item", "private"],
			"wall" => ["item", "wall"],
			"origin" => ["item", "origin"],
			"gravity" => ["item", "gravity"],
			"received" => ["item", "received"],			
			"name" => ["tag", "name"],
		],
		"query" => "FROM `post-tag`
			INNER JOIN `tag` ON `tag`.`id` = `post-tag`.`tid`
			INNER JOIN `item` ON `item`.`uri-id` = `post-tag`.`uri-id`
			WHERE `post-tag`.`type` = 1"
	],
	"workerqueue-view" => [
		"fields" => [
			"pid" => ["process", "pid"],
			"priority" => ["workerqueue", "priority"],
		],
		"query" => "FROM `process`
			INNER JOIN `workerqueue` ON `workerqueue`.`pid` = `process`.`pid`
			WHERE NOT `workerqueue`.`done`"
	],
];

