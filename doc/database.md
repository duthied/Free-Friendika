Database Tables
===============

* [Home](help)

| Table | Description |
|-------|-------------|
| [2fa_app_specific_password](help/database/db_2fa_app_specific_password) | Two-factor app-specific _password |
| [2fa_recovery_codes](help/database/db_2fa_recovery_codes) | Two-factor authentication recovery codes |
| [2fa_trusted_browser](help/database/db_2fa_trusted_browser) | Two-factor authentication trusted browsers |
| [account-suggestion](help/database/db_account-suggestion) | Account suggestion |
| [account-user](help/database/db_account-user) | Remote and local accounts |
| [apcontact](help/database/db_apcontact) | ActivityPub compatible contacts - used in the ActivityPub implementation |
| [application](help/database/db_application) | OAuth application |
| [application-marker](help/database/db_application-marker) | Timeline marker |
| [application-token](help/database/db_application-token) | OAuth user token |
| [arrived-activity](help/database/db_arrived-activity) | Id of arrived activities |
| [attach](help/database/db_attach) | file attachments |
| [cache](help/database/db_cache) | Stores temporary data |
| [channel](help/database/db_channel) | User defined Channels |
| [check-full-text-search](help/database/db_check-full-text-search) | Check for a full text search match in user defined channels before storing the message in the system |
| [config](help/database/db_config) | main configuration storage |
| [contact](help/database/db_contact) | contact table |
| [contact-relation](help/database/db_contact-relation) | Contact relations |
| [conv](help/database/db_conv) | private messages |
| [delayed-post](help/database/db_delayed-post) | Posts that are about to be distributed at a later time |
| [delivery-queue](help/database/db_delivery-queue) | Delivery data for posts for the batch processing |
| [diaspora-contact](help/database/db_diaspora-contact) | Diaspora compatible contacts - used in the Diaspora implementation |
| [diaspora-interaction](help/database/db_diaspora-interaction) | Signed Diaspora Interaction |
| [endpoint](help/database/db_endpoint) | ActivityPub endpoints - used in the ActivityPub implementation |
| [event](help/database/db_event) | Events |
| [fetch-entry](help/database/db_fetch-entry) |  |
| [fetched-activity](help/database/db_fetched-activity) | Id of fetched activities |
| [fsuggest](help/database/db_fsuggest) | friend suggestion stuff |
| [group](help/database/db_group) | privacy circles, circle info |
| [group_member](help/database/db_group_member) | privacy circles, member info |
| [gserver](help/database/db_gserver) | Global servers |
| [gserver-tag](help/database/db_gserver-tag) | Tags that the server has subscribed |
| [hook](help/database/db_hook) | addon hook registry |
| [inbox-entry](help/database/db_inbox-entry) | Incoming activity |
| [inbox-entry-receiver](help/database/db_inbox-entry-receiver) | Receiver for the incoming activity |
| [inbox-status](help/database/db_inbox-status) | Status of ActivityPub inboxes |
| [intro](help/database/db_intro) |  |
| [item-uri](help/database/db_item-uri) | URI and GUID for items |
| [key-value](help/database/db_key-value) | A key value storage |
| [locks](help/database/db_locks) |  |
| [mail](help/database/db_mail) | private messages |
| [mailacct](help/database/db_mailacct) | Mail account data for fetching mails |
| [manage](help/database/db_manage) | table of accounts that can manage each other |
| [notification](help/database/db_notification) | notifications |
| [notify](help/database/db_notify) | [Deprecated] User notifications |
| [notify-threads](help/database/db_notify-threads) |  |
| [oembed](help/database/db_oembed) | cache for OEmbed queries |
| [openwebauth-token](help/database/db_openwebauth-token) | Store OpenWebAuth token to verify contacts |
| [parsed_url](help/database/db_parsed_url) | cache for 'parse_url' queries |
| [pconfig](help/database/db_pconfig) | personal (per user) configuration storage |
| [permissionset](help/database/db_permissionset) |  |
| [photo](help/database/db_photo) | photo storage |
| [post](help/database/db_post) | Structure for all posts |
| [post-activity](help/database/db_post-activity) | Original remote activity |
| [post-category](help/database/db_post-category) | post relation to categories |
| [post-collection](help/database/db_post-collection) | Collection of posts |
| [post-content](help/database/db_post-content) | Content for all posts |
| [post-counts](help/database/db_post-counts) | Original remote activity |
| [post-delivery](help/database/db_post-delivery) | Delivery data for posts for the batch processing |
| [post-delivery-data](help/database/db_post-delivery-data) | Delivery data for items |
| [post-engagement](help/database/db_post-engagement) | Engagement data per post |
| [post-history](help/database/db_post-history) | Post history |
| [post-link](help/database/db_post-link) | Post related external links |
| [post-media](help/database/db_post-media) | Attached media |
| [post-question](help/database/db_post-question) | Question |
| [post-question-option](help/database/db_post-question-option) | Question option |
| [post-searchindex](help/database/db_post-searchindex) | Content for all posts |
| [post-tag](help/database/db_post-tag) | post relation to tags |
| [post-thread](help/database/db_post-thread) | Thread related data |
| [post-thread-user](help/database/db_post-thread-user) | Thread related data per user |
| [post-user](help/database/db_post-user) | User specific post data |
| [post-user-notification](help/database/db_post-user-notification) | User post notifications |
| [process](help/database/db_process) | Currently running system processes |
| [profile](help/database/db_profile) | user profiles data |
| [profile_field](help/database/db_profile_field) | Custom profile fields |
| [push_subscriber](help/database/db_push_subscriber) | Used for OStatus: Contains feed subscribers |
| [register](help/database/db_register) | registrations requiring admin approval |
| [report](help/database/db_report) |  |
| [report-post](help/database/db_report-post) | Individual posts attached to a moderation report |
| [report-rule](help/database/db_report-rule) | Terms of service rule lines relevant to a moderation report |
| [search](help/database/db_search) |  |
| [session](help/database/db_session) | web session storage |
| [storage](help/database/db_storage) | Data stored by Database storage backend |
| [subscription](help/database/db_subscription) | Push Subscription for the API |
| [tag](help/database/db_tag) | tags and mentions |
| [user](help/database/db_user) | The local users |
| [user-contact](help/database/db_user-contact) | User specific public contact data |
| [user-gserver](help/database/db_user-gserver) | User settings about remote servers |
| [userd](help/database/db_userd) | Deleted usernames |
| [verb](help/database/db_verb) | Activity Verbs |
| [worker-ipc](help/database/db_worker-ipc) | Inter process communication between the frontend and the worker |
| [workerqueue](help/database/db_workerqueue) | Background tasks queue entries |
