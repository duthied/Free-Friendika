Connectors
==========

* [Home](help)

Connectors allow you to connect with external social networks and services.
They are only required for posting to existing accounts on Twitter or GNU Social.
There is also a connector for accessing your email INBOX.

If the following network connectors are installed on your system, select the following links to visit the appropriate settings page and configure them for your account:

* [Twitter](/settings/addon)
* [GNU Social](/settings/addon)
* [Email](/settings)

Instructions For Connecting To People On Specific Services
==========================================================

Friendica
---

You can either connect to others by providing your Identity Address on the 'Connect' page of any Friendica member.
Or you can put their Identity Address into the Connect box on your [Contacts](contacts) page. 


Diaspora
---

Add the Diaspora 'handle' to the 'Connect/Follow' text box on your [Contacts](contacts) page. 


GNU Social
---

This is described as the "federated social web" or OStatus contacts. 

Please note that there are **no** privacy provisions on the OStatus network.
Any message which is delivered to **any** OStatus member is visible to anybody in the world and will negate any privacy settings that you have in effect.
These messages will also turn up in public searches. 

Since OStatus communications do not use authentication, if you select the profile privacy option to hide your profile and messages from unknown viewers, OStatus members will **not** be able to receive your communications. 

To connect with an OStatus member insert their profile URL or Identity address into the Connect box on your [Contacts](contacts) page.

The GNU Social connector may be used if you wish posts to appear on an OStatus site using an existing OStatus account. 
It is not necessary to do this, as you may 'follow' OStatus members from Friendica and they may follow you (by placing their own Identity Address into your 'Connect' page).

Blogger, Wordpress, RSS feeds, arbitrary web pages
---

Put the URL into the Connect box on your [Contacts](contacts) page.
PLease note that you will not be able to reply to these contacts. 

This feed reader feature will allow you to _connect_ with millions of pages on the internet.
All that the pages need to have is a discoverable feed using either the RSS or Atom syndication format, and which provides an author name and a site image in a form which we can extract. 

Twitter
---

To follow a Twitter member, the Twitter-Connector (Addon) needs to be configured on your node.
If this is the case put the URL of the Twitter member's main page into the Connect box on your [Contacts](contacts) page.
To reply, you must have the Twitter connector installed, and reply using your own status editor.
Begin the message with @twitterperson replacing with the Twitter username.

Email
---

If the php module for IMAP support is available on your server, Friendica can connect to email contacts as well.
Configure the email connector from your [Settings](settings) page.
Once this has been done, you may enter an email address to connect with using the Connect box on your [Contacts](contacts) page.
They must be the sender of a message which is currently in your INBOX for the connection to succeed.
You may include email contacts in private conversations.
