Implemented API calls
===
The Friendica API aims to be compatible to the [GNU Social API](http://skilledtests.com/wiki/Twitter-compatible_API) and the [Twitter API](https://dev.twitter.com/rest/public). 

Please refer to the linked documentation for further information.

## Implemented API calls

### General
#### Unsupported parameters
* cursor: Not implemented in GNU Social
* trim_user: Not implemented in GNU Social
* contributor_details: Not implemented in GNU Social
* place_id: Not implemented in GNU Social
* display_coordinates: Not implemented in GNU Social
* include_rts: To-Do
* include_my_retweet: Retweets in Friendica are implemented in a different way

#### Different behaviour
* screen_name: The nick name in friendica is only unique in each network but not for all networks. The users are searched in the following priority: Friendica, StatusNet/GNU Social, Diaspora, pump.io, Twitter. If no contact was found by this way, then the first contact is taken.
* include_entities: Default is "false". If set to "true" then the plain text is formatted so that links are having descriptions.

#### Return values
* cid: Contact id of the user (important for "contact_allow" and "contact_deny")
* network: network of the user

### account/rate_limit_status

### account/verify_credentials
#### Parameters
* skip_status: Don't show the "status" field. (Default: false)
* include_entities: "true" shows entities for pictures and links (Default: false)

### conversation/show
Unofficial Twitter command. It shows all direct answers (excluding the original post) to a given id.

#### Parameters
* id: id of the post
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts 
* trim_user 
* contributor_details 

### direct_messages
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* skip_status 

### direct_messages/all
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"

### direct_messages/conversation
Shows all direct messages of a conversation
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* uri: URI of the conversation

### direct_messages/new
#### Parameters
* user_id: id of the user 
* screen_name: screen name (for technical reasons, this value is not unique!)
* text: The message
* replyto: ID of the replied direct message
* title: Title of the direct message

### direct_messages/sent
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* include_entities: "true" shows entities for pictures and links (Default: false)

### favorites
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* user_id
* screen_name

Favorites aren't displayed to other users, so "user_id" and "screen_name". So setting this value will result in an empty array.

### favorites/create
#### Parameters
* id
* include_entities: "true" shows entities for pictures and links (Default: false)

### favorites/destroy
#### Parameters
* id
* include_entities: "true" shows entities for pictures and links (Default: false)

### followers/ids
#### Parameters
* stringify_ids: Should the id numbers be sent as text (true) or number (false)? (default: false)

#### Unsupported parameters
* user_id
* screen_name
* cursor 

Friendica doesn't allow showing followers of other users.

### friendica/photo
#### Parameters
* photo_id: Resource id of a photo.

Returns data of a picture with the given resource.

### friendica/photos/list

Returns a list of all photo resources of the logged in user.

### friends/ids
#### Parameters
* stringify_ids: Should the id numbers be sent as text (true) or number (false)? (default: false)

#### Unsupported parameters
* user_id
* screen_name
* cursor 

Friendica doesn't allow showing friends of other users.

### help/test

### media/upload
#### Parameters
* media: image data

### oauth/request_token
#### Parameters
* oauth_callback 

#### Unsupported parameters
* x_auth_access_type 

### oauth/access_token
#### Parameters
* oauth_verifier 

#### Unsupported parameters
* x_auth_password 
* x_auth_username 
* x_auth_mode 

### statuses/destroy
#### Parameters
* id: message number
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* trim_user 

### statuses/followers
* include_entities: "true" shows entities for pictures and links (Default: false)

### statuses/friends
* include_entities: "true" shows entities for pictures and links (Default: false)

### statuses/friends_timeline
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts 
* trim_user 
* contributor_details 

### statuses/home_timeline
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts 
* trim_user 
* contributor_details 

### statuses/mentions
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts 
* trim_user 
* contributor_details 

### statuses/public_timeline
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* trim_user 

### statuses/replies
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts 
* trim_user 
* contributor_details 

### statuses/retweet
#### Parameters
* id: message number
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* trim_user 

### statuses/show
#### Parameters
* id: message number
* conversation: if set to "1" show all messages of the conversation with the given id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_my_retweet 
* trim_user 

### statuses/update, statuses/update_with_media
#### Parameters
* title: Title of the status
* status: Status in text format
* htmlstatus: Status in HTML format
* in_reply_to_status_id
* lat: latitude
* long: longitude
* media: image data
* source: Application name
* group_allow
* contact_allow
* group_deny
* contact_deny
* network
* include_entities: "true" shows entities for pictures and links (Default: false)
* media_ids: (By now only a single value, no array) 

#### Unsupported parameters
* trim_user
* place_id
* display_coordinates

### statuses/user_timeline
#### Parameters
* user_id: id of the user 
* screen_name: screen name (for technical reasons, this value is not unique!)
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts 
* trim_user 
* contributor_details 

### statusnet/config

### statusnet/version

#### Unsupported parameters
* user_id
* screen_name
* cursor 

Friendica doesn't allow showing followers of other users.

### users/search
#### Parameters
* q: name of the user 

#### Unsupported parameters
* page
* count
* include_entities

### users/show
#### Parameters
* user_id: id of the user 
* screen_name: screen name (for technical reasons, this value is not unique!)
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* user_id
* screen_name
* cursor 

Friendica doesn't allow showing friends of other users.

## Not Implemented API calls
The following API calls are implemented in GNU Social but not in Friendica: (incomplete)

* statuses/retweets_of_me
* friendships/create
* friendships/destroy
* friendships/exists
* friendships/show
* account/update_profile_background_image
* account/update_profile_image
* blocks/create
* blocks/destroy

The following API calls from the Twitter API aren't implemented neither in Friendica nor in GNU Social:

* statuses/mentions_timeline
* statuses/retweets/:id
* statuses/oembed
* statuses/retweeters/ids
* statuses/lookup
* direct_messages/show
* search/tweets
* direct_messages/destroy
* friendships/no_retweets/ids
* friendships/incoming
* friendships/outgoing
* friendships/update
* friends/list
* friendships/lookup
* account/settings
* account/update_delivery_device
* account/update_profile
* account/update_profile_background_image
* account/update_profile_image
* blocks/list
* blocks/ids
* users/lookup
* users/show
* users/search
* account/remove_profile_banner
* account/update_profile_banner
* users/profile_banner
* mutes/users/create
* mutes/users/destroy
* mutes/users/ids
* mutes/users/list
* users/suggestions/:slug
* users/suggestions
* users/suggestions/:slug/members
* favorites/list
* lists/list
* lists/statuses
* lists/members/destroy
* lists/memberships
* lists/subscribers
* lists/subscribers/create
* lists/subscribers/show
* lists/subscribers/destroy
* lists/members/create_all
* lists/members/show
* lists/members
* lists/members/create
* lists/destroy
* lists/update
* lists/create
* lists/show
* lists/subscriptions
* lists/members/destroy_all
* lists/ownerships
* saved_searches/list
* saved_searches/show/:id
* saved_searches/create
* saved_searches/destroy/:id
* geo/id/:place_id
* geo/reverse_geocode
* geo/search
* geo/place
* trends/place
* trends/available
* help/configuration
* help/languages
* help/privacy
* help/tos
* trends/closest
* users/report_spam

## Usage Examples
### BASH / cURL
Betamax has documentated some example API usage from a [bash script](https://en.wikipedia.org/wiki/Bash_(Unix_shell) employing [curl](https://en.wikipedia.org/wiki/CURL) (see [his posting](https://betamax65.de/display/betamax65/43539)).

    /usr/bin/curl -u USER:PASS https://YOUR.FRIENDICA.TLD/api/statuses/update.xml -d source="some source id" -d status="the status you want to post"

### Python
The [RSStoFriedika](https://github.com/pafcu/RSStoFriendika) code can be used as an example of how to use the API with python. The lines for posting are located at [line 21](https://github.com/pafcu/RSStoFriendika/blob/master/RSStoFriendika.py#L21) and following.

    def tweet(server, message, group_allow=None):
        url = server + '/api/statuses/update'
        urllib2.urlopen(url, urllib.urlencode({'status': message,'group_allow[]':group_allow}, doseq=True))

There is also a [module for python 3](https://bitbucket.org/tobiasd/python-friendica) for using the API.
