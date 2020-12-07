# Settings

* [Home](help)

If you are the admin of a Friendica node, you have access to the **Admin Panel** where you can configure your Friendica node.

## Overview

In the main page of the admin panel you will see an information summary about your node.

### Queues

The three numbers shown are respectively:
- The retry queue: These outgoing messages couldn't be received by the remote host, and will be resent at longer intervals before being dropped entirely after 30 days.
- The deferred queue: These internal tasks failed and will be retried at most 14 times.
- The task queue: These internal tasks are queued for execution during the next background worker run.

### Additional information

Then you get an overview of the accounts on your node, which can be moderated in the "Users" section of the panel.
As well as an overview of the currently active addons.
The list is linked, so you can have quick access to the Addon settings.
And finally you are informed about the version of Friendica you have installed.
If you contact the developers with a bug or problem, please also mention the version of your node.

The admin panel is separated into subsections accessible from the side bar of the panel.

## Site

This section of the admin panel contains the main configuration of your Friendica node.
It is separated into several sub-section beginning with the basic settings at the top, advancing towards the bottom of the page.

Most configuration options have a help text in the admin panel.
Therefore this document does not yet cover all the options

### Basic Settings

#### Banner/Logo

Set the content for the site banner.
The default logo is the Friendica logo and name.
You may wish to provide HTML/CSS to style and/or position this content, as it may not be themed by default.

#### Language

This option will set the default language for the node.
It is used as fall back setting should Friendica fail to recognize the visitors preferences and can be overwritten by user settings.

The Friendica community offers some translations.
Some more complete then others.
See [this help page](/help/translations) for more information about the translation process.

#### System Theme

Choose a theme to be the default system theme.
This can be over-ridden by user profiles.
Default theme is `vier` at the moment.

You may also want to set a special theme for mobile interfaces.
Which may or may not be necessary depending of the mobile friendliness of the desktop theme you have chosen.
The `vier` theme for instance is mobile friendly.

### Registration

#### Register policy

With this drop down selector you can set the nodes registration policy.
You can chose between the following modes:

* **open**: Everybody can register a new account and start using it right away.
* **requires approval**: Everybody can register a new account, but the admin has to approve it before it can be used.
* **closed**: No new registrations are possible.

##### Invitation based registry

Additionally to the setting in the admin panel, you can decide if registrations are only possible using an invitation code or not.
To enable invitation based registration, you have to set the `invitation_only` setting to `true` in the `system` section of the [config/local.config.php](/help/Config) file.
If you want to use this method, the registration policy has to be set to either *open* or *requires approval*.

#### Check Full Names

You may find a lot of spammers trying to register on your site.
During testing we discovered that since these registrations were automatic, the "Full Name" field was often set to just an account name with no space between first and last name.
If you would like to support people with only one name as their full name, you may change this setting to true.
Default is false.

#### OpenID

By default, OpenID may be used for both registration and logins.
If you do not wish to make OpenID facilities available on your system (at all), set 'no_openid' to true.
Default is false.

#### Multiple Registrations

The ability to create "Pages" requires a person to register more than once.
Your site configuration can block registration (or require approval to register).
By default, logged in users can register additional accounts for use as pages.
These will still require approval if the registration policy is set to *require approval*
You may prohibit logged in users from creating additional accounts by setting *block multiple registrations* to true.
Default is false.

### File upload

#### File storage backend

Set the backend used by Friendica to store uploaded file data.
Two storage backends are avaiable with Friendica:

- **Database** : Data is stored in a dedicated table in database (`storage`)
- **Filesystem** : Data is stored as file on the filesystem.

More storage backends can be avaiable from third-party addons.
If you use those, please refer to the documentation of those addons for further information.

Default value is 'Database (legacy)': it's the legacy way used to store data directly in database.

Existing data can be moved to the current active backend using the ['storage move' console command](help/tools)

If selected backend has configurable options, new fields are shown here.

##### Filesystem: Storage base path

The base path where Filesystem storage backend saves data.

For maximum security, this path should be outside the folder tree served by the web server: this way files can't be downloaded bypassing the privacy checks.

Default value is `storage`, that is the `storage` folder in Friendica code root folder.


#### Maximum Image Size

Maximum size in bytes of uploaded images.
The default is set to 0, which means no limits.

### Policies

#### Global Directory

This configures the URL to update the global directory, and is supplied in the default configuration.
The undocumented part is that if this is not set, the global directory is completely unavailable to the application.
This allows a private community to be completely isolated from the global network.

#### Force Publish

By default, each user can choose on their Settings page whether or not to have their profile published in the site directory.
This setting forces all profiles on this site to be listed in the site directory and there is no option provided to the user to change it.
Default is false.

#### Block Public

Set to true to block public access to all otherwise public personal pages on this site unless you are currently logged in.
This blocks the viewing of profiles, friends, photos, the site directory and search pages to unauthorised persons.
A side effect is that entries from this site will not appear in the global directory.
We recommend specifically disabling that also (setting is described elsewhere on this page).
Note: this is specifically for sites that desire to be "standalone" and do not wish to be connected to any other Friendica sites.
Unauthorised persons will also not be able to request friendship with site members.
Default is false.
Available in version 2.2 or greater.

#### Community pages for Visitors

The community pages show all public postings, separated by their origin being local or the entire network.
With this setting you can select which community pages will be shown to visitors of your Friendica node.
Your local users will always have access to both pages.

**Note**: Several settings, like users hiding their contacts from the public will prevent the postings to show up on the global community page.

#### Allowed Friend Domains

Comma separated list of domains which are allowed to establish friendships with this site.
Wildcards are accepted.
By default, any (valid) domain may establish friendships with this site.

This is useful if you want to setup a closed network for educational groups, cooperatives and similar communities that don't want to communicate with the rest of the network.

#### Allowed Email Domains

Comma separated list of domains which are allowed in email addresses for registrations to this site.
This can lockout those who are not part of this organisation from registering here.
Wildcards are accepted.
By default, any (valid) email address is allowed in registrations.

#### Allow Users to set remote_self

If you enable the `Allow Users to set remote_self` users can select Atom feeds from their contact list being their *remote self* in the advanced contact settings.
Which means that postings by the remote self are automatically reposted by Friendica in their names.

This feature can be used to let the user mirror e.g. blog postings into their Friendica postings.
It is disabled by default, as it causes additional load on the server and may be misused to distribute SPAM.

As admin of the node you can also set this flag directly in the database.
Before doing so, you should be sure you know what you do and have a backup of the database.

#### Explicit Content

If you are running a node with explicit content, you can announce this with this option.
When checked an information flag will be set in the published information about your node.
(Should *Publish Server Information* be enabled.)

Additionally a note will be displayed on the registration page for new users.

### Advanced

#### Proxy Configuration Settings

If your site uses a proxy to connect to the internet, you may use these settings to communicate with the outside world.
The outside world still needs to be able to see your website, or this will not be very useful.

#### Network Timeout

How long to wait on a network communication before timing out.
Value is in seconds.
Default is 60 seconds.
Set to 0 for unlimited (not recommended).

#### Verify SSL Certificates

By default Friendica allows SSL communication between websites that have "self-signed" SSL certificates.
For the widest compatibility with browsers and other networks we do not recommend using self-signed certificates, but we will not prevent you from using them.
SSL encrypts all the data transmitted between sites (and to your browser).
This allows you to have completely encrypted communications, and also protect your login session from hijacking.
Self-signed certificates can be generated for free, without paying top-dollar for a website SSL certificate.
However these aren't looked upon favourably in the security community because they can be subject to so-called "man-in-the-middle" attacks.
If you wish, you can turn on strict certificate checking.
This will mean you cannot connect (at all) to self-signed SSL sites.

#### Check upstream version

If this option is enabled your Friendica node will check the upstream version once per day from the github repository.
You can select if the stable version or the development version should be checked out.
If there is a new version published, you will get notified in the admin panel summary page.

### Auto Discovered Contact Directory

### Performance

### Worker

This section allows you to configure the background process that is triggered by the `cron` job that was created during the installation.
The process does check the available system resources before creating a new worker for a task.
Because of this, it may happen that the maximum number of worker processes you allow will not be reached.

If your server setup does not allow you to use the `proc_open` function of PHP, please disable it in this section.

The tasks for the background process have priorities.
To guarantee that important tasks are executed even though the system has a lot of work to do, it is useful to enable the *fastlane*.

Should you not be able to run a cron job on your server, you can also activate the *frontend* worker.
If you have done so, you can call `example.com/worker` (replace example.com with your actual domain name) on a regular basis from an external service.
This will then trigger the execution of the background process.

### Relocate

## Users

This section of the panel let the admin control the users registered on the node.

If you have selected "Requires approval" for the *Register policy* in the general nodes configuration, new registrations will be listed at the top of the page.
There the admin can then approve or disapprove the request.

Below the new registration block the current accounts on the Friendica node are listed.
You can sort the user list by name, email, registration date, date of last login, date of last posting and the account type.
Here the admin can also block/unblock users from accessing the node or delete the accounts entirely.

In the last section of the page admins can create new accounts on the node.
The password for the new account will be send by email to the chosen email address.

## Addons

This page is for selecting and configuration of extensions for Friendica which have to be placed into the `/addon` subdirectory of your Friendica installation.
You are presented with a long list of available addons.
The name of each addon is linked to a separate page for that addon which offers more information and configuration possibilities.
Also shown is the version of the addon and an indicator if the addon is currently active or not.

When you update your node and the addons they may have to be reloaded.
To simplify this process there is a button at the top of the page to reload all active Addons.

## Themes

The Themes section of the admin panel works similar to the Addons section but let you control the themes on your Friendica node.
Each theme has a dedicated subpage showing the current status, some information about the theme and a screen-shot of the Friendica interface using the theme.
Should the theme offer special settings, admins can set a global default value here.

You can activate and deactivate themes on their dedicated sub-pages thus making them available for the users of the node.
To select a default theme for the Friendica node, see the *Site* section of the admin panel.

## Additional Features

There are several optional features in Friendica like the *dislike* button.
In this section of the admin panel you can select a default setting for your node and eventually fix it, so users cannot change the setting anymore.

## DB Updates

Should the database structure of Friendica change, it will apply the changes automatically.
In case you are suspecting the update might not have worked, you can use this section of the admin panel to check the situation.

## Inspect Queue

In the admin panel summary there are two numbers for the message queues.
The second number represents messages which could not be delivered and are queued for later retry.
If this number goes sky-rocking you might ask yourself which recipient is not receiving.

Behind the inspect queue section of the admin panel you will find a list of the messages that could not be delivered.
The listing is sorted by the recipient name so identifying potential broken communication lines should be simple.
These lines might be broken for various reasons.
The receiving end might be off-line, there might be a high system load and so on.

Don't panic!
Friendica will not queue messages for all time but will sort out *dead* nodes automatically after a while and remove messages from the queue then.

## Server Blocklist

This page allows to block all communications (inbound and outbound) with a specific domain name.
Each blocked domain entry requires a reason that will be displayed on the [friendica](/friendica) page.
Matching is exact, blocking a domain doesn't block subdomains.

## Federation Statistics

The federation statistics page gives you a short summery of the nodes/servers/pods of the decentralized social network federation your node knows.
These numbers are not complete and only contain nodes from networks Friendica federates directly with.

## Delete Item

Using this page an admin can delete postings and eventually associated discussion threads from their Friendica node.
To do so, they need to know the GUID of the posting.
This can be found on the `/display` page of the posting, it is the last part of the URL displayed in the browsers navigation bar.
You can get to the `/display` page by following the *Link to source*.

## Addon Features

Some of the addons you can install for your Friendica node have settings which have to be set by the admin.
All those addons will be listed in this area of the admin panels side bar with their names.

## Logs

The log section of the admin panel is separated into two pages.
On the first, following the "log" link, you can configure how much Friendica shall log.
And on the second you can read the log.

You should not place your logs into any directory that is accessible from the web.
If you have to, and you are using the default configuration from Apache, you should choose a name for the logfile ending in ``.log`` or ``.out``.
Should you use another web server, please make sure that you have the correct access rules in place so that your log files are not accessible.

There are five different log levels: Normal, Trace, Debug, Data and All.
Specifying different verbosity of information and data written out to the log file.
Normally you should not need to log at all.
The *DEBUG* level will show a good deal of information about system activity but will not include detailed data.
In the *ALL* level Friendica will log everything to the file.
But due to the volume of information we recommend only enabling this when you are tracking down a specific problem.

**The amount of data can grow the filesize of the logfile quickly**.
You should set up some kind of [log rotation](https://en.wikipedia.org/wiki/Log_rotation) to keep the log file from growing too big.

**Known Issues**: The filename ``friendica.log`` can cause problems depending on your server configuration (see [issue 2209](https://github.com/friendica/friendica/issues/2209)).

By default PHP warnings and error messages are suppressed.
If you want to enable those, you have to activate them in the ``config/local.config.php`` file.
Use the following settings to redirect PHP errors to a file.

Config:

	error_reporting(E_ERROR | E_WARNING | E_PARSE );
	ini_set('error_log','php.out');
	ini_set('log_errors','1');
	ini_set('display_errors', '0');

This will put all PHP errors in the file php.out (which must be writeable by the webserver).
Undeclared variables are occasionally referenced in the program and therefore we do not recommend using `E_NOTICE` or `E_ALL`.
The vast majority of issues reported at these levels are completely harmless.
Please report to the developers any errors you encounter in the logs using the recommended settings above.
They generally indicate issues which need to be resolved.

If you encounter a blank (white) page when using the application, view the PHP logs - as this almost always indicates an error has occurred.

## Diagnostics

In this section of the admin panel you find two tools to investigate what Friendica sees for certain resources.
These tools can help to clarify communication problems.

For the *probe address* Friendica will display information for the address provided.

With the second tool *check webfinger* you can request information about the thing identified by a webfinger (`someone@example.com`).

# Exceptions to the rule

There are four exceptions to the rule, that all the config will be read from the data base.
These are the data base settings, the admin account settings, the path of PHP and information about an eventual installation of the node in a sub-directory of the (sub)domain.

## DB Settings

With the following settings, you specify the data base server, the username and password for Friendica and the database to use.

	'database' => [
		'hostname' => 'localhost',
		'username' => 'mysqlusername',
		'password' => 'mysqlpassword',
		'database' => 'mysqldatabasename',
		'charset' => 'utf8mb4',
	],

## Admin users

You can set one, or more, accounts to be *Admin*.
By default this will be the one account you create during the installation process.
But you can expand the list of email addresses by any used email address you want.
Registration of new accounts with a listed email address is not possible.

	'config' => [
		'admin_email' => 'you@example.com, buddy@example.com',
	],

## PHP Path

Some of Friendica's processes are running in the background.
For this you need to specify the path to the PHP binary to be used.

	'config' => [
		'php_path' => '/usr/bin/php',
	],

## Subdirectory configuration

It is possible to install Friendica into a subdirectory of your web server.
We strongly discourage you from doing so, as this will break federation to other networks (e.g. Diaspora, GNU Social, Hubzilla)
Say you have a subdirectory for tests and put Friendica into a further subdirectory, the config would be:

	'system' => [
		'urlpath' => 'tests/friendica',
	],

## Other exceptions

Furthermore there are some experimental settings, you can read-up in the [Config values that can only be set in config/local.config.php](help/Config) section of the documentation.

