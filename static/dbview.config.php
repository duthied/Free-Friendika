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
	"post-view" => [
		"fields" => [
			"id" => ["item", "id"],
			"item_id" => ["item", "id"],
			"post-user-id" => ["post-user", "id"],
			"uid" => ["item", "uid"],
			"parent" => ["item", "parent"],
			"uri" => ["item", "uri"],
			"uri-id" => ["item", "uri-id"],
			"parent-uri" => ["item", "parent-uri"],
			"parent-uri-id" => ["item", "parent-uri-id"],
			"thr-parent" => ["item", "thr-parent"],
			"thr-parent-id" => ["item", "thr-parent-id"],
			"guid" => ["item", "guid"],
			"type" => ["item", "type"],
			"wall" => ["item", "wall"],
			"gravity" => ["item", "gravity"],
			"extid" => ["item", "extid"],
			"created" => ["item", "created"],
			"edited" => ["item", "edited"],
			"commented" => ["item", "commented"],
			"received" => ["item", "received"],
			"changed" => ["item", "changed"],
			"post-type" => ["item", "post-type"],
			"private" => ["item", "private"],
			"pubmail" => ["item", "pubmail"],
			"moderated" => ["item", "moderated"],
			"visible" => ["item", "visible"],
			"starred" => ["item", "starred"],
			"bookmark" => ["item", "bookmark"],
			"unseen" => ["item", "unseen"],
			"deleted" => ["item", "deleted"],
			"origin" => ["item", "origin"],
			"forum_mode" => ["item", "forum_mode"],
			"mention" => ["item", "mention"],
			"global" => ["item", "global"],
			"network" => ["item", "network"],
			"vid" => ["item", "vid"],
			"psid" => ["item", "psid"],
			"verb" => "IF (`item`.`vid` IS NULL, '', `verb`.`name`)",
			"title" => ["post-content", "title"],
			"content-warning" => ["post-content", "content-warning"],
			"raw-body" => ["post-content", "raw-body"],
			"body" => ["post-content", "body"],
			"rendered-hash" => ["post-content", "rendered-hash"],
			"rendered-html" => ["post-content", "rendered-html"],
			"language" => ["post-content", "language"],
			"plink" => ["post-content", "plink"],
			"location" => ["post-content", "location"],
			"coord" => ["post-content", "coord"],
			"app" => ["post-content", "app"],
			"object-type" => ["post-content", "object-type"],
			"object" => ["post-content", "object"],
			"target-type" => ["post-content", "target-type"],
			"target" => ["post-content", "target"],
			"resource-id" => ["post-content", "resource-id"],
			"contact-id" => ["item", "contact-id"],
			"contact-link" => ["contact", "url"],
			"contact-addr" => ["contact", "addr"],
			"contact-name" => ["contact", "name"],
			"contact-nick" => ["contact", "nick"],
			"contact-avatar" => ["contact", "thumb"],
			"contact-network" => ["contact", "network"],
			"contact-blocked" => ["contact", "blocked"],
			"contact-hidden" => ["contact", "hidden"],
			"contact-readonly" => ["contact", "readonly"],
			"contact-archive" => ["contact", "archive"],
			"contact-pending" => ["contact", "pending"],
			"contact-rel" => ["contact", "rel"],
			"contact-uid" => ["contact", "uid"],
			"contact-contact-type" => ["contact", "contact-type"],
			"writable" => "IF (`item`.`network` IN ('apub', 'dfrn', 'dspr', 'stat'), true, `contact`.`writable`)",
			"self" => ["contact", "self"],
			"cid" => ["contact", "id"],
			"alias" => ["contact", "alias"],
			"photo" => ["contact", "photo"],
			"name-date" => ["contact", "name-date"],
			"uri-date" => ["contact", "uri-date"],
			"avatar-date" => ["contact", "avatar-date"],
			"thumb" => ["contact", "thumb"],
			"dfrn-id" => ["contact", "dfrn-id"],
			"author-id" => ["item", "author-id"],
			"author-link" => ["author", "url"],
			"author-addr" => ["author", "addr"],
			"author-name" => "IF (`contact`.`url` = `author`.`url` AND `contact`.`name` != '', `contact`.`name`, `author`.`name`)",
			"author-nick" => ["author", "nick"],
			"author-avatar" => "IF (`contact`.`url` = `author`.`url` AND `contact`.`thumb` != '', `contact`.`thumb`, `author`.`thumb`)",
			"author-network" => ["author", "network"],
			"author-blocked" => ["author", "blocked"],
			"author-hidden" => ["author", "hidden"],
			"owner-id" => ["item", "owner-id"],
			"owner-link" => ["owner", "url"],
			"owner-addr" => ["owner", "addr"],
			"owner-name" => "IF (`contact`.`url` = `owner`.`url` AND `contact`.`name` != '', `contact`.`name`, `owner`.`name`)",
			"owner-nick" => ["owner", "nick"],
			"owner-avatar" => "IF (`contact`.`url` = `owner`.`url` AND `contact`.`thumb` != '', `contact`.`thumb`, `owner`.`thumb`)",
			"owner-network" => ["owner", "network"],
			"owner-blocked" => ["owner", "blocked"],
			"owner-hidden" => ["owner", "hidden"],
			"causer-id" => ["item", "causer-id"],
			"causer-link" => ["causer", "url"],
			"causer-addr" => ["causer", "addr"],
			"causer-name" => ["causer", "name"],
			"causer-nick" => ["causer", "nick"], 
			"causer-avatar" => ["causer", "thumb"],
			"causer-network" => ["causer", "network"],
			"causer-blocked" => ["causer", "blocked"],
			"causer-hidden" => ["causer", "hidden"],
			"causer-contact-type" => ["causer", "contact-type"],
			"postopts" => ["post-delivery-data", "postopts"],
			"inform" => ["post-delivery-data", "inform"],
			"delivery_queue_count" => ["post-delivery-data", "queue_count"],
			"delivery_queue_done" => ["post-delivery-data", "queue_done"],
			"delivery_queue_failed" => ["post-delivery-data", "queue_failed"],
			"allow_cid" => "IF (`item`.`psid` IS NULL, '', `permissionset`.`allow_cid`)",
			"allow_gid" => "IF (`item`.`psid` IS NULL, '', `permissionset`.`allow_gid`)",
			"deny_cid" => "IF (`item`.`psid` IS NULL, '', `permissionset`.`deny_cid`)",
			"deny_gid" => "IF (`item`.`psid` IS NULL, '', `permissionset`.`deny_gid`)",
			"event-id" => ["item", "event-id"],
			"event-created" => ["event", "created"],
			"event-edited" => ["event", "edited"],
			"event-start" => ["event", "start"],
			"event-finish" => ["event", "finish"],
			"event-summary" => ["event", "summary"],
			"event-desc" => ["event", "desc"],
			"event-location" => ["event", "location"],
			"event-type" => ["event", "type"],
			"event-nofinish" => ["event", "nofinish"],
			"event-adjust" => ["event", "adjust"],
			"event-ignore" => ["event", "ignore"],
			"signed_text" => ["diaspora-interaction", "interaction"],
			"parent-guid" => ["parent-item", "guid"],
			"parent-network" => ["parent-item", "network"],
			"parent-author-id" => ["parent-item", "author-id"],
			"parent-author-link" => ["parent-item-author", "url"],  
			"parent-author-name" => ["parent-item-author", "name"],
			"parent-author-network" => ["parent-item-author", "network"], 
		],
		"query" => "FROM `item`
			LEFT JOIN `post-user` ON `post-user`.`uri-id` = `item`.`uri-id` AND `post-user`.`uid` = `item`.`uid`
			STRAIGHT_JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			STRAIGHT_JOIN `contact` AS `author` ON `author`.`id` = `item`.`author-id`
			STRAIGHT_JOIN `contact` AS `owner` ON `owner`.`id` = `item`.`owner-id`
			STRAIGHT_JOIN `contact` AS `causer` ON `causer`.`id` = `item`.`causer-id`
			LEFT JOIN `verb` ON `verb`.`id` = `item`.`vid`
			LEFT JOIN `event` ON `event`.`id` = `item`.`event-id`
			LEFT JOIN `diaspora-interaction` ON `diaspora-interaction`.`uri-id` = `item`.`uri-id`
			LEFT JOIN `post-content` ON `post-content`.`uri-id` = `item`.`uri-id`
			LEFT JOIN `post-delivery-data` ON `post-delivery-data`.`uri-id` = `item`.`uri-id` AND `item`.`origin`
			LEFT JOIN `permissionset` ON `permissionset`.`id` = `item`.`psid`
			STRAIGHT_JOIN `item` AS `parent-item` ON `parent-item`.`uri-id` = `item`.`parent-uri-id` AND `parent-item`.`uid` = `item`.`uid`
			STRAIGHT_JOIN `contact` AS `parent-item-author` ON `parent-item-author`.`id` = `parent-item`.`author-id`"
	],
	"post-thread-view" => [
		"fields" => [
			"id" => ["item", "id"],
			"iid" => ["item", "id"],
			"item_id" => ["item", "id"],
			"uid" => ["post-thread-user", "uid"],
			"parent" => ["item", "parent"],
			"uri" => ["item", "uri"],
			"uri-id" => ["post-thread", "uri-id"],
			"parent-uri" => ["item", "parent-uri"],
			"parent-uri-id" => ["item", "parent-uri-id"],
			"thr-parent" => ["item", "thr-parent"],
			"thr-parent-id" => ["item", "thr-parent-id"],
			"guid" => ["item", "guid"],
			"type" => ["item", "type"],
			"wall" => ["post-thread-user", "wall"],
			"gravity" => ["item", "gravity"],
			"extid" => ["item", "extid"],
			"created" => ["post-thread", "created"],
			"edited" => ["item", "edited"],
			"commented" => ["post-thread", "commented"],
			"received" => ["post-thread", "received"],
			"changed" => ["post-thread", "changed"],
			"post-type" => ["item", "post-type"],
			"private" => ["item", "private"],
			"pubmail" => ["post-thread-user", "pubmail"],
			"moderated" => ["item", "moderated"],
			"ignored" => ["post-thread-user", "ignored"],
			"visible" => ["item", "visible"],
			"starred" => ["post-thread-user", "starred"],
			"bookmark" => ["item", "bookmark"],
			"unseen" => ["post-thread-user", "unseen"],
			"deleted" => ["item", "deleted"],
			"origin" => ["post-thread-user", "origin"],
			"forum_mode" => ["post-thread-user", "forum_mode"],
			"mention" => ["item", "mention"],
			"global" => ["item", "global"],
			"network" => ["post-thread", "network"],
			"vid" => ["item", "vid"],
			"psid" => ["post-thread-user", "psid"],
			"verb" => "IF (`item`.`vid` IS NULL, '', `verb`.`name`)",
			"title" => ["post-content", "title"],
			"content-warning" => ["post-content", "content-warning"],
			"raw-body" => ["post-content", "raw-body"],
			"body" => ["post-content", "body"],
			"rendered-hash" => ["post-content", "rendered-hash"],
			"rendered-html" => ["post-content", "rendered-html"],
			"language" => ["post-content", "language"],
			"plink" => ["post-content", "plink"],
			"location" => ["post-content", "location"],
			"coord" => ["post-content", "coord"],
			"app" => ["post-content", "app"],
			"object-type" => ["post-content", "object-type"],
			"object" => ["post-content", "object"],
			"target-type" => ["post-content", "target-type"],
			"target" => ["post-content", "target"],
			"resource-id" => ["post-content", "resource-id"],
			"contact-id" => ["post-thread-user", "contact-id"],
			"contact-link" => ["contact", "url"],
			"contact-addr" => ["contact", "addr"],
			"contact-name" => ["contact", "name"],
			"contact-nick" => ["contact", "nick"],
			"contact-avatar" => ["contact", "thumb"],
			"contact-network" => ["contact", "network"],
			"contact-blocked" => ["contact", "blocked"],
			"contact-hidden" => ["contact", "hidden"],
			"contact-readonly" => ["contact", "readonly"],
			"contact-archive" => ["contact", "archive"],
			"contact-pending" => ["contact", "pending"],
			"contact-rel" => ["contact", "rel"],
			"contact-uid" => ["contact", "uid"],
			"contact-contact-type" => ["contact", "contact-type"],
			"writable" => "IF (`item`.`network` IN ('apub', 'dfrn', 'dspr', 'stat'), true, `contact`.`writable`)",
			"self" => ["contact", "self"],
			"cid" => ["contact", "id"],
			"alias" => ["contact", "alias"],
			"photo" => ["contact", "photo"],
			"name-date" => ["contact", "name-date"],
			"uri-date" => ["contact", "uri-date"],
			"avatar-date" => ["contact", "avatar-date"],
			"thumb" => ["contact", "thumb"],
			"dfrn-id" => ["contact", "dfrn-id"],
			"author-id" => ["post-thread", "author-id"],
			"author-link" => ["author", "url"],
			"author-addr" => ["author", "addr"],
			"author-name" => "IF (`contact`.`url` = `author`.`url` AND `contact`.`name` != '', `contact`.`name`, `author`.`name`)",
			"author-nick" => ["author", "nick"],
			"author-avatar" => "IF (`contact`.`url` = `author`.`url` AND `contact`.`thumb` != '', `contact`.`thumb`, `author`.`thumb`)",
			"author-network" => ["author", "network"],
			"author-blocked" => ["author", "blocked"],
			"author-hidden" => ["author", "hidden"],
			"owner-id" => ["post-thread", "owner-id"],
			"owner-link" => ["owner", "url"],
			"owner-addr" => ["owner", "addr"],
			"owner-name" => "IF (`contact`.`url` = `owner`.`url` AND `contact`.`name` != '', `contact`.`name`, `owner`.`name`)",
			"owner-nick" => ["owner", "nick"],
			"owner-avatar" => "IF (`contact`.`url` = `owner`.`url` AND `contact`.`thumb` != '', `contact`.`thumb`, `owner`.`thumb`)",
			"owner-network" => ["owner", "network"],
			"owner-blocked" => ["owner", "blocked"],
			"owner-hidden" => ["owner", "hidden"],
			"causer-id" => ["item", "causer-id"],
			"causer-link" => ["causer", "url"],
			"causer-addr" => ["causer", "addr"],
			"causer-name" => ["causer", "name"],
			"causer-nick" => ["causer", "nick"], 
			"causer-avatar" => ["causer", "thumb"],
			"causer-network" => ["causer", "network"],
			"causer-blocked" => ["causer", "blocked"],
			"causer-hidden" => ["causer", "hidden"],
			"causer-contact-type" => ["causer", "contact-type"],
			"postopts" => ["post-delivery-data", "postopts"],
			"inform" => ["post-delivery-data", "inform"],
			"delivery_queue_count" => ["post-delivery-data", "queue_count"],
			"delivery_queue_done" => ["post-delivery-data", "queue_done"],
			"delivery_queue_failed" => ["post-delivery-data", "queue_failed"],
			"allow_cid" => "IF (`post-thread-user`.`psid` IS NULL, '', `permissionset`.`allow_cid`)",
			"allow_gid" => "IF (`post-thread-user`.`psid` IS NULL, '', `permissionset`.`allow_gid`)",
			"deny_cid" => "IF (`post-thread-user`.`psid` IS NULL, '', `permissionset`.`deny_cid`)",
			"deny_gid" => "IF (`post-thread-user`.`psid` IS NULL, '', `permissionset`.`deny_gid`)",
			"event-id" => ["item", "event-id"],
			"event-created" => ["event", "created"],
			"event-edited" => ["event", "edited"],
			"event-start" => ["event", "start"],
			"event-finish" => ["event", "finish"],
			"event-summary" => ["event", "summary"],
			"event-desc" => ["event", "desc"],
			"event-location" => ["event", "location"],
			"event-type" => ["event", "type"],
			"event-nofinish" => ["event", "nofinish"],
			"event-adjust" => ["event", "adjust"],
			"event-ignore" => ["event", "ignore"],
			"signed_text" => ["diaspora-interaction", "interaction"],
			"parent-guid" => ["parent-item", "guid"],
			"parent-network" => ["parent-item", "network"],
			"parent-author-id" => ["parent-item", "author-id"],
			"parent-author-link" => ["parent-item-author", "url"],  
			"parent-author-name" => ["parent-item-author", "name"],
			"parent-author-network" => ["parent-item-author", "network"], 
		],
		"query" => "FROM `post-thread`
			STRAIGHT_JOIN `post-thread-user` ON `post-thread-user`.`uri-id` = `post-thread`.`uri-id`
			STRAIGHT_JOIN `item` ON `item`.`uri-id` = `post-thread`.`uri-id` AND `item`.`uid` = `post-thread-user`.`uid`
			STRAIGHT_JOIN `contact` ON `contact`.`id` = `post-thread-user`.`contact-id`
			STRAIGHT_JOIN `contact` AS `author` ON `author`.`id` = `post-thread`.`author-id`
			STRAIGHT_JOIN `contact` AS `owner` ON `owner`.`id` = `post-thread`.`owner-id`
			STRAIGHT_JOIN `contact` AS `causer` ON `causer`.`id` = `post-thread`.`causer-id`
			LEFT JOIN `verb` ON `verb`.`id` = `item`.`vid`
			LEFT JOIN `event` ON `event`.`id` = `item`.`event-id`
			LEFT JOIN `diaspora-interaction` ON `diaspora-interaction`.`uri-id` = `post-thread`.`uri-id`
			LEFT JOIN `post-content` ON `post-content`.`uri-id` = `post-thread`.`uri-id`
			LEFT JOIN `post-delivery-data` ON `post-delivery-data`.`uri-id` = `post-thread`.`uri-id` AND `post-thread-user`.`origin`
			LEFT JOIN `permissionset` ON `permissionset`.`id` = `post-thread-user`.`psid`
			STRAIGHT_JOIN `item` AS `parent-item` ON `parent-item`.`id` = `item`.`parent`
			STRAIGHT_JOIN `contact` AS `parent-item-author` ON `parent-item-author`.`id` = `parent-item`.`author-id`"
	],
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
	"network-item-view" => [
		"fields" => [
			"uri-id" => ["item", "parent-uri-id"],
			"uri" => ["item", "parent-uri"],
			"parent" => ["item", "parent"],
			"received" => ["item", "received"],
			"commented" => ["item", "commented"],
			"created" => ["item", "created"],
			"uid" => ["item", "uid"],
			"starred" => ["item", "starred"],
			"mention" => ["item", "mention"],
			"network" => ["item", "network"],
			"unseen" => ["item", "unseen"],
			"gravity" => ["item", "gravity"],
			"contact-id" => ["item", "contact-id"],
			"contact-type" => ["ownercontact", "contact-type"],
		],
		"query" => "FROM `item`
			INNER JOIN `item` AS `parent-item` ON `parent-item`.`id` = `item`.`parent`
			STRAIGHT_JOIN `contact` ON `contact`.`id` = `parent-item`.`contact-id`
			LEFT JOIN `post-user` ON `post-user`.`uri-id` = `item`.`uri-id` AND `post-user`.`uid` = `parent-item`.`uid`
			LEFT JOIN `user-contact` AS `author` ON `author`.`uid` = `parent-item`.`uid` AND `author`.`cid` = `parent-item`.`author-id`
			LEFT JOIN `user-contact` AS `owner` ON `owner`.`uid` = `parent-item`.`uid` AND `owner`.`cid` = `parent-item`.`owner-id`
			LEFT JOIN `contact` AS `ownercontact` ON `ownercontact`.`id` = `parent-item`.`owner-id`
			WHERE `parent-item`.`visible` AND NOT `parent-item`.`deleted` AND NOT `parent-item`.`moderated`
			AND (NOT `contact`.`readonly` AND NOT `contact`.`blocked` AND NOT `contact`.`pending`)
			AND (`post-user`.`hidden` IS NULL OR NOT `post-user`.`hidden`)
			AND (`author`.`blocked` IS NULL OR NOT `author`.`blocked`)
			AND (`owner`.`blocked` IS NULL OR NOT `owner`.`blocked`)"
	],
	"network-thread-view" => [
		"fields" => [
			"uri-id" => ["post-thread", "uri-id"],
			"uri" => ["item", "uri"],
			"parent-uri-id" => ["item", "parent-uri-id"],
			"parent" => ["item", "id"],
			"received" => ["post-thread", "received"],
			"commented" => ["post-thread", "commented"],
			"created" => ["post-thread", "created"],
			"uid" => ["post-thread-user", "uid"],
			"starred" => ["post-thread-user", "starred"],
			"mention" => ["post-thread-user", "mention"],
			"network" => ["post-thread", "network"],
			"contact-id" => ["post-thread-user", "contact-id"],
			"contact-type" => ["ownercontact", "contact-type"],
		],
		"query" => "FROM `post-thread`
			STRAIGHT_JOIN `post-thread-user` ON `post-thread-user`.`uri-id` = `post-thread`.`uri-id`
			STRAIGHT_JOIN `item` ON `item`.`uri-id` = `post-thread`.`uri-id` AND `item`.`uid` = `post-thread-user`.`uid`
			STRAIGHT_JOIN `contact` ON `contact`.`id` = `post-thread-user`.`contact-id`
			LEFT JOIN `user-contact` AS `author` ON `author`.`uid` = `post-thread-user`.`uid` AND `author`.`cid` = `post-thread`.`author-id`
			LEFT JOIN `user-contact` AS `owner` ON `owner`.`uid` = `post-thread-user`.`uid` AND `owner`.`cid` = `post-thread`.`owner-id`
			LEFT JOIN `contact` AS `ownercontact` ON `ownercontact`.`id` = `post-thread`.`owner-id`
			WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
			AND (NOT `contact`.`readonly` AND NOT `contact`.`blocked` AND NOT `contact`.`pending`)
			AND (`post-thread-user`.`hidden` IS NULL OR NOT `post-thread-user`.`hidden`)
			AND (`author`.`blocked` IS NULL OR NOT `author`.`blocked`)
			AND (`owner`.`blocked` IS NULL OR NOT `owner`.`blocked`)"
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
			"blocked" => ["user", "blocked"],
			"block_reason" => ["contact", "block_reason"],
			"readonly" => ["contact", "readonly"],
			"writable" => ["contact", "writable"],
			"forum" => ["contact", "forum"],
			"prv" => ["contact", "prv"],
			"contact-type" => ["contact", "contact-type"],
			"manually-approve" => ["contact", "manually-approve"],
			"hidden" => ["contact", "hidden"],
			"archive" => ["contact", "archive"],
			"pending" => ["contact", "pending"],
			"deleted" => ["contact", "deleted"],
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

