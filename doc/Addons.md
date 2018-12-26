Friendica Addon development
==============

* [Home](help)

Please see the sample addon 'randplace' for a working example of using some of these features.
Addons work by intercepting event hooks - which must be registered.
Modules work by intercepting specific page requests (by URL path).

Addon names cannot contain spaces or other punctuation and are used as filenames and function names.
You may supply a "friendly" name within the comment block.
Each addon must contain both an install and an uninstall function based on the addon name.
For instance "addon1name_install()".
These two functions take no arguments and are usually responsible for registering (and unregistering) event hooks that your addon will require.
The install and uninstall functions will also be called (i.e. re-installed) if the addon changes after installation.
Therefore your uninstall should not destroy data and install should consider that data may already exist.
Future extensions may provide for "setup" amd "remove".

Addons should contain a comment block with the four following parameters:

    /*
     * Name: My Great Addon
     * Description: This is what my addon does. It's really cool.
     * Version: 1.0
     * Author: John Q. Public <john@myfriendicasite.com>
     */

Please also add a README or README.md file to the addon directory.
It will be displayed in the admin panel and should include some further information in addition to the header information.

## PHP addon hooks

Register your addon hooks during installation.

    Addon::registerHook($hookname, $file, $function);

$hookname is a string and corresponds to a known Friendica PHP hook.

$file is a pathname relative to the top-level Friendica directory.
This *should* be 'addon/*addon_name*/*addon_name*.php' in most cases.

$function is a string and is the name of the function which will be executed when the hook is called.

### Arguments
Your hook callback functions will be called with at least one and possibly two arguments

    function myhook_function(App $a, &$b) {

    }


If you wish to make changes to the calling data, you must declare them as reference variables (with `&`) during function declaration.

#### $a
$a is the Friendica `App` class.
It contains a wealth of information about the current state of Friendica:

* which module has been called,
* configuration information,
* the page contents at the point the hook was invoked,
* profile and user information, etc.

It is recommeded you call this `$a` to match its usage elsewhere.

#### $b
$b can be called anything you like.
This is information specific to the hook currently being processed, and generally contains information that is being immediately processed or acted on that you can use, display, or alter.
Remember to declare it with `&` if you wish to alter it.

## Global stylesheets

If your addon requires adding a stylesheet on all pages of Friendica, add the following hook:

```php
function <addon>_install()
{
	Addon::registerHook('head', __FILE__, '<addon>_head');
	...
}


function <addon>_head(App $a)
{
	$a->registerStylesheet(__DIR__ . '/relative/path/to/addon/stylesheet.css');
}
```

`__DIR__` is the folder path of your addon.

## JavaScript

### Global scripts

If your addon requires adding a script on all pages of Friendica, add the following hook:


```php
function <addon>_install()
{
	Addon::registerHook('footer', __FILE__, '<addon>_footer');
	...
}

function <addon>_footer(App $a)
{
	$a->registerFooterScript(__DIR__ . '/relative/path/to/addon/script.js');
}
```

`__DIR__` is the folder path of your addon.

### JavaScript hooks

The main Friendica script provides hooks via events dispatched on the `document` property.
In your Javascript file included as described above, add your event listener like this:

```js
document.addEventListener(name, callback);
```

- *name* is the name of the hook and corresponds to a known Friendica JavaScript hook.
- *callback* is a JavaScript anonymous function to execute.

More info about Javascript event listeners: https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener

#### Current JavaScript hooks

##### postprocess_liveupdate
Called at the end of the live update process (XmlHttpRequest) and on a post preview.
No additional data is provided.

## Modules

Addons may also act as "modules" and intercept all page requests for a given URL path.
In order for a addon to act as a module it needs to define a function "addon_name_module()" which takes no arguments and needs not do anything.

If this function exists, you will now receive all page requests for "http://my.web.site/addon_name" - with any number of URL components as additional arguments.
These are parsed into an array $a->argv, with a corresponding $a->argc indicating the number of URL components.
So http://my.web.site/addon/arg1/arg2 would look for a module named "addon" and pass its module functions the $a App structure (which is available to many components).
This will include:

```php
$a->argc = 3
$a->argv = array(0 => 'addon', 1 => 'arg1', 2 => 'arg2');
```

Your module functions will often contain the function addon_name_content(App $a), which defines and returns the page body content.
They may also contain addon_name_post(App $a) which is called before the _content function and typically handles the results of POST forms.
You may also have addon_name_init(App $a) which is called very early on and often does module initialisation.

## Templates

If your addon needs some template, you can use the Friendica template system.
Friendica uses [smarty3](http://www.smarty.net/) as a template engine.

Put your tpl files in the *templates/* subfolder of your addon.

In your code, like in the function addon_name_content(), load the template file and execute it passing needed values:

```php
# load template file. first argument is the template name,
# second is the addon path relative to friendica top folder
$tpl = Renderer::getMarkupTemplate('mytemplate.tpl', 'addon/addon_name/');

# apply template. first argument is the loaded template,
# second an array of 'name' => 'values' to pass to template
$output = Renderer::replaceMacros($tpl, array(
	'title' => 'My beautiful addon',
));
```

See also the wiki page [Quick Template Guide](https://github.com/friendica/friendica/wiki/Quick-Template-Guide).

## Current PHP hooks

### authenticate
Called when a user attempts to login.
`$b` is an array containing:

- **username**: the supplied username
- **password**: the supplied password
- **authenticated**: set this to non-zero to authenticate the user.
- **user_record**: successful authentication must also return a valid user record from the database

### logged_in
Called after a user has successfully logged in.
`$b` contains the `$a->user` array.

### display_item
Called when formatting a post for display.
$b is an array:

- **item**: The item (array) details pulled from the database
- **output**: the (string) HTML representation of this item prior to adding it to the page

### post_local
Called when a status post or comment is entered on the local system.
`$b` is the item array of the information to be stored in the database.
Please note: body contents are bbcode - not HTML.

### post_local_end
Called when a local status post or comment has been stored on the local system.
`$b` is the item array of the information which has just been stored in the database.
Please note: body contents are bbcode - not HTML

### post_remote
Called when receiving a post from another source. This may also be used to post local activity or system generated messages.
`$b` is the item array of information to be stored in the database and the item body is bbcode.

### settings_form
Called when generating the HTML for the user Settings page.
`$b` is the HTML string of the settings page before the final `</form>` tag.

### settings_post
Called when the Settings pages are submitted.
`$b` is the $_POST array.

### addon_settings
Called when generating the HTML for the addon settings page.
`$b` is the (string) HTML of the addon settings page before the final `</form>` tag.

### addon_settings_post
Called when the Addon Settings pages are submitted.
`$b` is the $_POST array.

### profile_post
Called when posting a profile page.
`$b` is the $_POST array.

### profile_edit
Called prior to output of profile edit page.
`$b` is an array containing:

- **profile**: profile (array) record from the database
- **entry**: the (string) HTML of the generated entry

### profile_advanced
Called when the HTML is generated for the Advanced profile, corresponding to the Profile tab within a person's profile page.
`$b` is the HTML string representation of the generated profile.
The profile array details are in `$a->profile`.

### directory_item
Called from the Directory page when formatting an item for display.
`$b` is an array:

- **contact**: contact record array for the person from the database
- **entry**: the HTML string of the generated entry

### profile_sidebar_enter
Called prior to generating the sidebar "short" profile for a page.
`$b` is the person's profile array

### profile_sidebar
Called when generating the sidebar "short" profile for a page.
`$b` is an array:

- **profile**: profile record array for the person from the database
- **entry**: the HTML string of the generated entry

### contact_block_end
Called when formatting the block of contacts/friends on a profile sidebar has completed.
`$b` is an array:

- **contacts**: array of contacts
- **output**: the generated HTML string of the contact block

### bbcode
Called after conversion of bbcode to HTML.
`$b` is an HTML string converted text.

### html2bbcode
Called after tag conversion of HTML to bbcode (e.g. remote message posting)
`$b` is a string converted text

### head
Called when building the `<head>` sections.
Stylesheets should be registered using this hook.
`$b` is an HTML string of the `<head>` tag.

### page_header
Called after building the page navigation section.
`$b` is a string HTML of nav region.

### personal_xrd
Called prior to output of personal XRD file.
`$b` is an array:

- **user**: the user record array for the person
- **xml**: the complete XML string to be output

### home_content
Called prior to output home page content, shown to unlogged users.
`$b` is the HTML sring of section region.

### contact_edit
Called when editing contact details on an individual from the Contacts page.
$b is an array:

- **contact**: contact record (array) of target contact
- **output**: the (string) generated HTML of the contact edit page

### contact_edit_post
Called when posting the contact edit page.
`$b` is the `$_POST` array

### init_1
Called just after DB has been opened and before session start.
No hook data.

### page_end
Called after HTML content functions have completed.
`$b` is (string) HTML of content div.

### footer
Called after HTML content functions have completed.
Deferred Javascript files should be registered using this hook.
`$b` is (string) HTML of footer div/element.

### avatar_lookup
Called when looking up the avatar. `$b` is an array:

- **size**: the size of the avatar that will be looked up
- **email**: email to look up the avatar for
- **url**: the (string) generated URL of the avatar

### emailer_send_prepare
Called from `Emailer::send()` before building the mime message.
`$b` is an array of params to `Emailer::send()`.

- **fromName**: name of the sender
- **fromEmail**: email fo the sender
- **replyTo**: replyTo address to direct responses
- **toEmail**: destination email address
- **messageSubject**: subject of the message
- **htmlVersion**: html version of the message
- **textVersion**: text only version of the message
- **additionalMailHeader**: additions to the smtp mail header

### emailer_send
Called before calling PHP's `mail()`.
`$b` is an array of params to `mail()`.

- **to**
- **subject**
- **body**
- **headers**

### load_config
Called during `App` initialization to allow addons to load their own configuration file(s) with `App::loadConfigFile()`.

### nav_info
Called after the navigational menu is build in `include/nav.php`.
`$b` is an array containing `$nav` from `include/nav.php`.

### template_vars
Called before vars are passed to the template engine to render the page.
The registered function can add,change or remove variables passed to template.
`$b` is an array with:

- **template**: filename of template
- **vars**: array of vars passed to the template

### acl_lookup_end
Called after the other queries have passed.
The registered function can add, change or remove the `acl_lookup()` variables.

- **results**: array of the acl_lookup() vars

### prepare_body_init
Called at the start of prepare_body
Hook data:

- **item** (input/output): item array

### prepare_body_content_filter
Called before the HTML conversion in prepare_body. If the item matches a content filter rule set by an addon, it should
just add the reason to the filter_reasons element of the hook data.
Hook data:

- **item**: item array (input)
- **filter_reasons** (input/output): reasons array

### prepare_body
Called after the HTML conversion in `prepare_body()`.
Hook data:

- **item** (input): item array
- **html** (input/output): converted item body
- **is_preview** (input): post preview flag
- **filter_reasons** (input): reasons array

### prepare_body_final
Called at the end of `prepare_body()`.
Hook data:

- **item**: item array (input)
- **html**: converted item body (input/output)

### put_item_in_cache
Called after `prepare_text()` in `put_item_in_cache()`.
Hook data:

- **item** (input): item array
- **rendered-html** (input/output): final item body HTML
- **rendered-hash** (input/output): original item body hash

### magic_auth_success
Called when a magic-auth was successful.
Hook data:

    visitor => array with the contact record of the visitor
    url => the query string

## Complete list of hook callbacks

Here is a complete list of all hook callbacks with file locations (as of 24-Sep-2018). Please see the source for details of any hooks not documented above.

### index.php

    Hook::callAll('init_1');
    Hook::callAll('app_menu', $arr);
    Hook::callAll('page_content_top', $a->page['content']);
    Hook::callAll($a->module.'_mod_init', $placeholder);
    Hook::callAll($a->module.'_mod_init', $placeholder);
    Hook::callAll($a->module.'_mod_post', $_POST);
    Hook::callAll($a->module.'_mod_afterpost', $placeholder);
    Hook::callAll($a->module.'_mod_content', $arr);
    Hook::callAll($a->module.'_mod_aftercontent', $arr);
    Hook::callAll('page_end', $a->page['content']);

### include/api.php

    Hook::callAll('logged_in', $a->user);
    Hook::callAll('authenticate', $addon_auth);
    Hook::callAll('logged_in', $a->user);

### include/enotify.php

    Hook::callAll('enotify', $h);
    Hook::callAll('enotify_store', $datarray);
    Hook::callAll('enotify_mail', $datarray);
    Hook::callAll('check_item_notification', $notification_data);

### include/conversation.php

    Hook::callAll('conversation_start', $cb);
    Hook::callAll('render_location', $locate);
    Hook::callAll('display_item', $arr);
    Hook::callAll('display_item', $arr);
    Hook::callAll('item_photo_menu', $args);
    Hook::callAll('jot_tool', $jotplugins);

### include/text.php

    Hook::callAll('contact_block_end', $arr);
    Hook::callAll('poke_verbs', $arr);
    Hook::callAll('put_item_in_cache', $hook_data);
    Hook::callAll('prepare_body_init', $item);
    Hook::callAll('prepare_body_content_filter', $hook_data);
    Hook::callAll('prepare_body', $hook_data);
    Hook::callAll('prepare_body_final', $hook_data);

### include/items.php

    Hook::callAll('page_info_data', $data);

### mod/directory.php

    Hook::callAll('directory_item', $arr);

### mod/xrd.php

    Hook::callAll('personal_xrd', $arr);

### mod/ping.php

    Hook::callAll('network_ping', $arr);

### mod/parse_url.php

    Hook::callAll("parse_link", $arr);

### mod/manage.php

    Hook::callAll('home_init', $ret);

### mod/acl.php

    Hook::callAll('acl_lookup_end', $results);

### mod/network.php

    Hook::callAll('network_content_init', $arr);
    Hook::callAll('network_tabs', $arr);

### mod/friendica.php

    Hook::callAll('about_hook', $o);

### mod/subthread.php

    Hook::callAll('post_local_end', $arr);

### mod/profiles.php

    Hook::callAll('profile_post', $_POST);
    Hook::callAll('profile_edit', $arr);

### mod/settings.php

    Hook::callAll('addon_settings_post', $_POST);
    Hook::callAll('connector_settings_post', $_POST);
    Hook::callAll('display_settings_post', $_POST);
    Hook::callAll('settings_post', $_POST);
    Hook::callAll('addon_settings', $settings_addons);
    Hook::callAll('connector_settings', $settings_connectors);
    Hook::callAll('display_settings', $o);
    Hook::callAll('settings_form', $o);

### mod/photos.php

    Hook::callAll('photo_post_init', $_POST);
    Hook::callAll('photo_post_file', $ret);
    Hook::callAll('photo_post_end', $foo);
    Hook::callAll('photo_post_end', $foo);
    Hook::callAll('photo_post_end', $foo);
    Hook::callAll('photo_post_end', $foo);
    Hook::callAll('photo_post_end', intval($item_id));
    Hook::callAll('photo_upload_form', $ret);

### mod/profile.php

    Hook::callAll('profile_advanced', $o);

### mod/home.php

    Hook::callAll('home_init', $ret);
    Hook::callAll("home_content", $content);

### mod/poke.php

    Hook::callAll('post_local_end', $arr);

### mod/contacts.php

    Hook::callAll('contact_edit_post', $_POST);
    Hook::callAll('contact_edit', $arr);

### mod/tagger.php

    Hook::callAll('post_local_end', $arr);

### mod/lockview.php

    Hook::callAll('lockview_content', $item);

### mod/uexport.php

    Hook::callAll('uexport_options', $options);

### mod/register.php

    Hook::callAll('register_post', $arr);
    Hook::callAll('register_form', $arr);

### mod/item.php

    Hook::callAll('post_local_start', $_REQUEST);
    Hook::callAll('post_local', $datarray);
    Hook::callAll('post_local_end', $datarray);

### mod/editpost.php

    Hook::callAll('jot_tool', $jotplugins);

### src/Network/FKOAuth1.php

    Hook::callAll('logged_in', $a->user);

### src/Render/FriendicaSmartyEngine.php

    Hook::callAll("template_vars", $arr);

### src/App.php

    Hook::callAll('load_config');
    Hook::callAll('head');
    Hook::callAll('footer');

### src/Model/Item.php

    Hook::callAll('post_local', $item);
    Hook::callAll('post_remote', $item);
    Hook::callAll('post_local_end', $posted_item);
    Hook::callAll('post_remote_end', $posted_item);
    Hook::callAll('tagged', $arr);
    Hook::callAll('post_local_end', $new_item);

### src/Model/Contact.php

    Hook::callAll('contact_photo_menu', $args);
    Hook::callAll('follow', $arr);

### src/Model/Profile.php

    Hook::callAll('profile_sidebar_enter', $profile);
    Hook::callAll('profile_sidebar', $arr);
    Hook::callAll('profile_tabs', $arr);
    Hook::callAll('zrl_init', $arr);
    Hook::callAll('magic_auth_success', $arr);

### src/Model/Event.php

    Hook::callAll('event_updated', $event['id']);
    Hook::callAll("event_created", $event['id']);

### src/Model/User.php

    Hook::callAll('register_account', $uid);
    Hook::callAll('remove_user', $user);

### src/Content/Text/BBCode.php

    Hook::callAll('bbcode', $text);
    Hook::callAll('bb2diaspora', $text);

### src/Content/Text/HTML.php

    Hook::callAll('html2bbcode', $message);

### src/Content/Smilies.php

    Hook::callAll('smilie', $params);

### src/Content/Feature.php

    Hook::callAll('isEnabled', $arr);
    Hook::callAll('get', $arr);

### src/Content/ContactSelector.php

    Hook::callAll('network_to_name', $nets);
    Hook::callAll('gender_selector', $select);
    Hook::callAll('sexpref_selector', $select);
    Hook::callAll('marital_selector', $select);

### src/Content/OEmbed.php

    Hook::callAll('oembed_fetch_url', $embedurl, $j);

### src/Content/Nav.php

    Hook::callAll('page_header', $a->page['nav']);
    Hook::callAll('nav_info', $nav);

### src/Worker/Directory.php

    Hook::callAll('globaldir_update', $arr);

### src/Worker/Notifier.php

    Hook::callAll('notifier_end', $target_item);

### src/Worker/Queue.php

    Hook::callAll('queue_predeliver', $r);
    Hook::callAll('queue_deliver', $params);

### src/Module/Login.php

    Hook::callAll('authenticate', $addon_auth);
    Hook::callAll('login_hook', $o);

### src/Module/Logout.php

    Hook::callAll("logging_out");

### src/Object/Post.php

    Hook::callAll('render_location', $locate);
    Hook::callAll('display_item', $arr);

### src/Core/ACL.php

    Hook::callAll('contact_select_options', $x);
    Hook::callAll($a->module.'_pre_'.$selname, $arr);
    Hook::callAll($a->module.'_post_'.$selname, $o);
    Hook::callAll($a->module.'_pre_'.$selname, $arr);
    Hook::callAll($a->module.'_post_'.$selname, $o);
    Hook::callAll('jot_networks', $jotnets);

### src/Core/Authentication.php

    Hook::callAll('logged_in', $a->user);

### src/Core/Hook.php

    self::callSingle(self::getApp(), 'hook_fork', $fork_hook, $hookdata);

### src/Core/Worker.php

    Hook::callAll("proc_run", $arr);

### src/Util/Emailer.php

    Hook::callAll('emailer_send_prepare', $params);
    Hook::callAll("emailer_send", $hookdata);

### src/Util/Map.php

    Hook::callAll('generate_map', $arr);
    Hook::callAll('generate_named_map', $arr);
    Hook::callAll('Map::getCoordinates', $arr);

### src/Util/Network.php

    Hook::callAll('avatar_lookup', $avatar);

### src/Util/ParseUrl.php

    Hook::callAll("getsiteinfo", $siteinfo);

### src/Protocol/DFRN.php

    Hook::callAll('atom_feed_end', $atom);
    Hook::callAll('atom_feed_end', $atom);

### view/js/main.js

    document.dispatchEvent(new Event('postprocess_liveupdate'));
