Channels
=====

* [Home](help)

Channels are a way to discover new content or to display content that you might have missed otherwise.
There are several predefined channels, additionally you can create your own channels, based on some rules.

In the display settings in the section "Timelines" you can define which channels and other timelines you want to see in the "Channels" widget on the network page and which channels should appear in the menu bar at the top of the page.

Predefined Channels
---

* Whats hot: Posts from the last 24 hours with the most interactions.
* For you: Posts from the last 24 hours from contact you interact on a lot, or you follow (and the post has some comments) or posts from people you want to be notified when they post.
* Followers: Posts from the last 24 hours created by contacts that follow you, but you don't follow them.
* Images: Posts from the last 24 hours with pictures.
* Videos: Posts from the last 24 hours with videos
* Audio: Posts from the last 24 hours with audio

User defined Channels
---

In the "Channels" settings you can create your own channels.

Each channel is defined by these values:

* Label: This value is mandatory and is used for the menu label.
* Description: A short description of the content. This can help to keep the overview, when you have got a lot of channels.
* Access Key: When you want to access this channel via an access key, you can define it here. Pay attention to not use an already used one.
* Include Tags: Comma separated list of tags. A post will be used when it contains any of the listed tags.
* Exclude Tags: Comma separated list of tags. If a post contain any of these tags, then it will not be part of nthis channel.
* Full Text Search: This can be used to include or exclude content, based on the content and some additional keywords. It uses the "boolean mode" operators from MariaDB: https://mariadb.com/kb/en/full-text-index-overview/#in-boolean-mode
* Images, Videos, Audio: When selected, you will see content with the selected media type. This can be combined. If none of these fields are checked, you will see any content, with or without attacked media.

Additional keywords for the full text search
---

Additionally to the search for content, there are additional keywords that can be used in the full text search:

* from - Use "from:nickname" or "from:nickname@domain.tld" to search for posts from a specific author.
* to - Use "from:nickname" or "from:nickname@domain.tld" to search for posts with the given contact as receiver.
* group - Use "from:nickname" or "from:nickname@domain.tld" to search for group post of the given group.
* tag - Use "tag:tagname" to search for a specific tag.
* network - Use this to include or exclude some networks from your channel.
    * network:apub - ActivityPub (Used by the systems in the Fediverse)
    * network:dfrn - Legacy Friendica protocol. Nowayday Friendica mostly uses ActivityPub.
    * network:dspr - The Diaspora protocol is mainly used by Diaspora itself. Some other systems support the protocol as well like Hubzilla, Socialhome or Ganggo.
    * network:feed - RSS/Atom feeds
    * network:mail - Mails that had been imported via IMAP.
    * network:stat - The OStatus protocol is mainly used by old GNU Social installations.
    * network:dscs - Posts that are received by the Discourse connector.
    * network:tmbl - Posts that are received by the Tumblr connector.
    * network:bsky - Posts that are received by the Bluesky connector.
* visibility - You have the choice between different visibilities. You can only see unlisted or private posts that you have the access for.
    * visibility:public
    * visibility:unlisted
    * visibility:private

Remember that you can combine these kerywords.
So for example you can create a channel with all posts that talk about the Fediverse - that aren't posted in the Fediverse with the search terms: "fediverse -network:apub -network:dfrn"