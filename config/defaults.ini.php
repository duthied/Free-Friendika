<?php return <<<INI

; This file declares the default values for all the config values of Friendica.
; Please don't edit this file as its content may change in the upcoming versions.

[database]

; host (String)
; Hostname or IP address of the database server.
; Can contain the port number with the syntax "hostname:port".
hostname =

; user (String)
; Database user name. Please don't use "root".
username =

; pass (String)
; Database user password. Please don't use empty passwords.
password =

; base (String)
; Database name.
database =

; charset (String)
; Database connexion charset. Changing this value will likely corrupt special characters.
charset = utf8mb4

[config]

; admin_email (Comma-separated list)
; In order to perform system administration via the admin panel, this must precisely match the email address of the person logged in.
admin_email =

; max_import_size (Integer)
; Maximum body size of DFRN and Mail messages in characters. 0 is unlimited.
max_import_size = 200000

; php_path (String)
; Location of PHP command line processor
php_path = php

; register_policy (Constant)
; Your choices are REGISTER_OPEN, REGISTER_APPROVE, or REGISTER_CLOSED.
; Be certain to create your own personal account before setting REGISTER_CLOSED.
; REGISTER_APPROVE requires you set system.admin_email to the email address of an already registered person who can authorise
; and/or approve/deny the request.
register_policy = REGISTER_CLOSED

; register_text (String)
; Will be displayed prominently on the registration page.
register_text = ''

; sitename (String)
; Displayed server name
sitename = "Friendica Social Network"

[system]

; account_abandon_days (Integer)
; Will not waste system resources polling external sites for abandonded accounts.
; Enter 0 for no time limit.
account_abandon_days = 0

; addon (Comma-separated list)
; Manual list of addons which are enabled on this system.
addon =

; allowed_link_protocols (Array)
; Allowed protocols in links URLs, add at your own risk. http is always allowed.
allowed_link_protocols[] = ftp
allowed_link_protocols[] = ftps
allowed_link_protocols[] = mailto
allowed_link_protocols[] = cid
allowed_link_protocols[] = gopher

; always_show_preview (Boolean)
; Only show small preview picures.
always_show_preview = false

; archival_days (Integer)
; Number of days that we try to deliver content before we archive a contact.
archival_days = 32

; auth_cookie_lifetime (Integer)
; Number of days that should pass without any activity before a user who chose "Remember me" when logging in is considered logged out.
auth_cookie_lifetime = 7

; block_local_dir (Boolean)
; Deny public access to the local user directory.
block_local_dir = false

; config_adapter (jit|preload)
; Allow to switch the configuration adapter to improve performances at the cost of memory consumption.
config_adapter = jit

; curl_range_bytes (Integer)
; Maximum number of bytes that should be fetched. Default is 0, which mean "no limit".
curl_range_bytes = 0

; db_log (Path)
; Name of a logfile to log slow database queries
db_log =

; db_loglimit (Integer)
; If a database call lasts longer than this value in seconds it is logged.
; Inactive if system.db_log is empty
db_loglimit = 10

; db_log_index (Path)
; Name of a logfile to log queries with bad indexes
db_log_index =

; db_log_index_watch (Comma-separated list)
; Watchlist of indexes to watch
db_log_index_watch =

; db_loglimit_index (Integer)
; Number of index rows needed to be logged for indexes on the watchlist. 0 to disable.
db_loglimit_index = 0

; db_loglimit_index_high (Integer)
; Number of index rows to be logged anyway (for any index). 0 to disable.
db_loglimit_index_high = 0

; db_log_index_blacklist (Comma-separated list)
; Blacklist of indexes that shouldn't be watched
db_log_index_blacklist =

; dbclean_expire_conversation (Integer)
; When DBClean is enabled, any entry in the conversation table will be deleted after this many days.
; These data are normally needed only for debugging purposes and they are safe to delete.
dbclean_expire_conversation = 90

; default_timezone (String)
; Choose a default timezone. See https://secure.php.net/manual/en/timezones.php
; It only applies to timestamps for anonymous viewers.
default_timezone = UTC

; diaspora_test (Boolean)
; For development only. Disables the message transfer.
diaspora_test = false

; disable_email_validation (Boolean)
; Disables the check if a mail address is in a valid format and can be resolved via DNS.
disable_email_validation = false

; disable_url_validation (Boolean)
; Disables the DNS lookup of an URL.
disable_url_validation = false

; disable_password_exposed (Boolean)
; Disable the exposition check against the remote haveibeenpwned API on password change.
disable_password_exposed = false

; dlogfile (Path)
; location of the developer log file
dlogfile =

; dlogip (String)
; restricts develop log writes to requests originating from this IP address
dlogip =

; frontend_worker_timeout (Integer)
; Value in minutes after we think that a frontend task was killed by the webserver.
frontend_worker_timeout = 10

; hsts (Boolean)
; Enables the sending of HTTP Strict Transport Security headers
hsts = false

; ignore_cache (Boolean)
; For development only. Disables the item cache.
ignore_cache = false

; instances_social_key (String)
; Key to the API of https://instances.social which retrieves data about mastodon servers.
; See https://instances.social/api/token to get an API key.
instances_social_key =

; ipv4_resolve (Boolean)
; Resolve IPV4 addresses only. Don't resolve to IPV6.
ipv4_resolve = false

; invitation_only (Boolean)
; If set true registration is only possible after a current member of the node has send an invitation.
invitation_only = false

; jpeg_quality (Integer)
; Sets the ImageMagick quality level for JPEG images. Values ranges from 50 (awful) to 100 (near perfect).
jpeg_quality = 100

; language (String)
; Admin-created user default language.
; Two-letters ISO 639-1 code.
language = en

; like_no_comment (Boolean)
; Don't update the "commented" value of an item when it is liked.
like_no_comment = false

; local_block (Boolean)
; Used in conjunction with "block_public".
local_block = false

; local_search (Boolean)
; Blocks search for users who are not logged in to prevent crawlers from blocking your system.
local_search = false

; local_tags (Boolean)
; If activated, all hashtags will point to the local server.
local_tags = false

; max_connections (Integer)
; The maximum number of database connections which can be in use before the worker process is deferred to its next interval.
; When the system can't detect the maximum numbers of connection then this value can be used. Use 0 for auto-detection.
max_connections = 0

; max_connections_level (Integer 0-100)
; The maximum percentage of connections that are allowed to let the worker start.
max_connections_level = 75

; max_contact_queue (Integer)
; Maximum number of queue items for a single contact before subsequent messages are discarded.
max_contact_queue = 500

; max_batch_queue (Integer)
; Maximum number of batched queue items for a single contact before subsequent messages are discarded.
max_batch_queue = 1000

; max_image_length (Integer)
; An alternate way of limiting picture upload sizes.
; Specify the maximum pixel  length that pictures are allowed to be (for non-square pictures, it will apply to the longest side).
; Pictures longer than this length will be resized to be this length (on the longest side, the other side will be scaled appropriately).
; If you don't want to set a maximum length, set to -1.
max_image_length = -1

; max_processes_backend (Integer)
; Maximum number of concurrent database processes for background tasks.
max_processes_backend = 5

; max_processes_frontend (Integer)
; Maximum number of concurrent database processes for foreground tasks.
max_processes_frontend = 20

; maximagesize (Integer)
; Maximum size in bytes of an uploaded photo.
maximagesize = 800000

; min_poll_interval (Integer)
; minimal distance in minutes between two polls for a contact. Reasonable values are between 1 and 59.
min_poll_interval = 1

; no_regfullname (Boolean)
; Allow pseudonyms (true) or enforce a space between firstname and lastname in Full name, as an antispam measure (false).
no_regfullname = true

; session_handler (database|cache|native)
; Whether to use Cache to store session data or to use PHP native session storage.
session_handler = database

; cache_driver (database|memcache|memcached|redis)
; Whether to use Memcache or Memcached or Redis to store temporary cache.
cache_driver = database

; memcache_host (String)
; Host name of the memcache daemon.
memcache_host = 127.0.0.1

; memcache_port (Integer)
; Port number of the memcache daemon.
memcache_port = 11211

; memcached_hosts (Array)
; Array of Memcached servers info "host, port(, weight)".
memcached_hosts[] = 127.0.0.1, 11211

; redis_host (String)
; Host name of the redis daemon.
redis_host = 127.0.0.1

; redis_port (String)
; Port number of the redis daemon.
redis_port = 6379

; no_count (Boolean)
; Don't do count calculations (currently only when showing albums)
no_count = false

; no_oembed (Boolean)
; Don't use OEmbed to fetch more information about a link.
no_oembed = false

; no_smilies (Boolean)
; Don't show smilies.
no_smilies = false

; no_view_full_size (Boolean)
; Don't add the link "View full size" under a resized image.
no_view_full_size = false

; optimize_items (Boolean)
; Triggers an SQL command to optimize the item table before expiring items.
optimize_items = false

; pidfile (Path)
; Daemon pid file path. For example: pidfile = /path/to/daemon.pid
pidfile =

; urlpath (String)
; If you are using a subdirectory of your domain you will need to put the relative path (from the root of your domain) here.
; For instance if your URL is 'http://example.com/directory/subdirectory', set urlpath to 'directory/subdirectory'.
urlpath =

; paranoia (Boolean)
; Log out users if their IP address changed.
paranoia = false

; permit_crawling (Boolean)
; Restricts the search for not logged in users to one search per minute.
permit_crawling = false

; free_crawls (Integer)
; Number of "free" searches when "permit_crawling" is activated.
free_crawls = 10

; crawl_permit_period (Integer)
; Period in seconds between allowed searches when the number of free searches is reached and "permit_crawling" is activated.
crawl_permit_period = 60

; queue_no_dead_check (Boolean)
; Ignore if the target contact or server seems to be dead during queue delivery.
queue_no_dead_check = false

; rino_encrypt (Integer)
; Server-to-server private message encryption (RINO).
; Encryption will only be provided if this setting is set to a non zero value on both servers.
; Set to 0 to disable, 2 to enable, 1 is deprecated but wont need mcrypt.
rino_encrypt = 2

; worker_debug (Boolean)
; If enabled, it prints out the number of running processes split by priority.
worker_debug = false

; worker_fetch_limit (Integer)
; Number of worker tasks that are fetched in a single query.
worker_fetch_limit = 1

; profiler (Boolean)
; Enable internal timings to help optimize code. Needed for "rendertime" addon.
profiler = false

; png_quality (Integer)
; Sets the ImageMagick compression level for PNG images. Values ranges from 0 (uncompressed) to 9 (most compressed).
png_quality = 8

; proc_windows (Boolean)
; Should be enabled if Friendica is running under Windows.
proc_windows = false

; proxy_cache_time (Integer)
; Period in seconds after which the cache is cleared.
proxy_cache_time = 86400

; pushpoll_frequency (Integer)
; Frequency of contact poll for subhub contact using the DFRM or OStatus network
; Available values:
; - 5 = every month
; - 4 = every week
; - 3 = every day
; - 2 = twice a day
; - 1 = every hour
; - 0 = every minute
pushpoll_frequency = 3

; remove_multiplicated_lines (Boolean)
; If enabled, multiple linefeeds in items are stripped to a single one.
remove_multiplicated_lines = false

; sendmail_params (Boolean)
; Normal sendmail command parameters will be added when the PHP mail() function is called for sending e-mails.
; This ensures the Sender Email address setting is applied to the message envelope rather than the host's default address.
; Set to false if your non-sendmail agent is incompatible, or to restore old behavior of using the host address.
sendmail_params = true

; show_unsupported_addons (Boolean)
; Show all addons including the unsupported ones.
show_unsupported_addons = false

; show_unsupported_themes (Boolean)
; Show all themes including the unsupported ones.
show_unsupported_themes = false

; show_global_community_hint (Boolean)
; When the global community page is enabled, use this option to display a hint above the stream, that this is a collection of all public top-level postings that arrive on your node.
show_global_community_hint = false

; allowed themes (Comma-separated list)
; Themes users can change to in their settings
allowed_themes = 'quattro,vier,duepuntozero,smoothly'

; theme (String)
; System theme name
theme = vier

; throttle_limit_day (Integer)
; Maximum number of posts that a user can send per day with the API. 0 to disable daily throttling.
throttle_limit_day = 0

; throttle_limit_week (Integer)
; Maximum number of posts that a user can send per week with the API. 0 to disable weekly throttling.
throttle_limit_week = 0

; throttle_limit_month (Integer)
; Maximum number of posts that a user can send per month with the API. 0 to disable monthly throttling.
throttle_limit_month = 0

; worker_cooldown (Integer)
; Cooldown period in seconds after each worker function call.
worker_cooldown = 0

; worker_load_exponent (Integer)
; Default 3, which allows only 25% of the maximum worker queues when server load reaches around 37% of maximum load.
; For a linear response where 25% of worker queues are allowed at 75% of maximum load, set this to 1.
; Setting 0 would allow maximum worker queues at all times, which is not recommended.
worker_load_exponent = 3

; directory (String)
; URL of the global directory
directory = https://dir.friendi.social

; xrd_timeout (Integer)
; Timeout in seconds for fetching the XRD links.
xrd_timeout = 20

[experimental]

; exp_themes (Boolean)
; Show experimental themes in user settings.
exp_themes = false

[theme]

; hide_eventlist (Boolean)
; Don't show the birthdays and events on the profile and network page
hide_eventlist = false

[jabber]

; debug (Boolean)
; Enable debug level for the jabber account synchronisation.
debug = false

; lockpath (Path)
; Must be writable by the ejabberd process. if set then it will prevent the running of multiple processes.
lockpath =

INI;
// Keep this line