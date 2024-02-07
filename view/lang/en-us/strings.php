<?php

if(! function_exists("string_plural_select_en_us")) {
function string_plural_select_en_us($n){
	$n = intval($n);
	return intval($n != 1);
}}
$a->strings['Unable to locate original post.'] = 'Unable to locate original post.';
$a->strings['Empty post discarded.'] = 'Empty post discarded.';
$a->strings['Item not found.'] = 'Item not found.';
$a->strings['Permission denied.'] = 'Permission denied.';
$a->strings['No valid account found.'] = 'No valid account found.';
$a->strings['Password reset request issued. Check your email.'] = 'Password reset request issued. Please check your email.';
$a->strings['
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email, the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.'] = '
		Dear %1$s,
			A request was received at "%2$s" to reset your account password
		To confirm this request, please select the verification link
		below or paste it into your web browser\'s address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided; ignore or delete this email, as the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.';
$a->strings['
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s'] = '
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s';
$a->strings['Password reset requested at %s'] = 'Password reset requested at %s';
$a->strings['Request could not be verified. (You may have previously submitted it.) Password reset failed.'] = 'Request could not be verified. (You may have previously submitted it.) Password reset failed.';
$a->strings['Request has expired, please make a new one.'] = 'Request has expired, please make a new one.';
$a->strings['Forgot your Password?'] = 'Reset My Password';
$a->strings['Enter your email address and submit to have your password reset. Then check your email for further instructions.'] = 'Enter email address or nickname to reset your password. You will receive further instruction via email.';
$a->strings['Nickname or Email: '] = 'Nickname or email: ';
$a->strings['Reset'] = 'Reset';
$a->strings['Password Reset'] = 'Forgotten password?';
$a->strings['Your password has been reset as requested.'] = 'Your password has been reset as requested.';
$a->strings['Your new password is'] = 'Your new password is';
$a->strings['Save or copy your new password - and then'] = 'Save or copy your new password - and then';
$a->strings['click here to login'] = 'click here to login';
$a->strings['Your password may be changed from the <em>Settings</em> page after successful login.'] = 'Your password may be changed from the <em>Settings</em> page after successful login.';
$a->strings['
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records (or change your password immediately to
			something that you will remember).
		'] = '
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records (or change your password immediately to
			something that you will remember).
		';
$a->strings['
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		'] = '
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		';
$a->strings['Your password has been changed at %s'] = 'Your password has been changed at %s';
$a->strings['New Message'] = 'New Message';
$a->strings['No recipient selected.'] = 'No recipient selected.';
$a->strings['Unable to locate contact information.'] = 'Unable to locate contact information.';
$a->strings['Message could not be sent.'] = 'Message could not be sent.';
$a->strings['Message collection failure.'] = 'Message collection failure.';
$a->strings['Discard'] = 'Discard';
$a->strings['Messages'] = 'Messages';
$a->strings['Conversation not found.'] = 'Conversation not found.';
$a->strings['Please enter a link URL:'] = 'Please enter a link URL:';
$a->strings['Send Private Message'] = 'Send private message';
$a->strings['To:'] = 'To:';
$a->strings['Subject:'] = 'Subject:';
$a->strings['Your message:'] = 'Your message:';
$a->strings['Upload photo'] = 'Upload photo';
$a->strings['Insert web link'] = 'Insert web link';
$a->strings['Please wait'] = 'Please wait';
$a->strings['Submit'] = 'Submit';
$a->strings['No messages.'] = 'No messages.';
$a->strings['Message not available.'] = 'Message not available.';
$a->strings['Delete message'] = 'Delete message';
$a->strings['D, d M Y - g:i A'] = 'D, d M Y - g:i A';
$a->strings['Delete conversation'] = 'Delete conversation';
$a->strings['No secure communications available. You <strong>may</strong> be able to respond from the sender\'s profile page.'] = 'No secure communications available. You <strong>may</strong> be able to respond from the sender\'s profile page.';
$a->strings['Send Reply'] = 'Send reply';
$a->strings['Unknown sender - %s'] = 'Unknown sender - %s';
$a->strings['You and %s'] = 'Me and %s';
$a->strings['%s and You'] = '%s and me';
$a->strings['%d message'] = [
	0 => '%d message',
	1 => '%d messages',
];
$a->strings['Personal Notes'] = 'Personal notes';
$a->strings['Personal notes are visible only by yourself.'] = 'Personal notes are only visible to you.';
$a->strings['Save'] = 'Save';
$a->strings['User not found.'] = 'User not found.';
$a->strings['Photo Albums'] = 'Photo Albums';
$a->strings['Recent Photos'] = 'Recent photos';
$a->strings['Upload New Photos'] = 'Upload new photos';
$a->strings['everybody'] = 'everybody';
$a->strings['Contact information unavailable'] = 'Contact information unavailable';
$a->strings['Album not found.'] = 'Album not found.';
$a->strings['Album successfully deleted'] = 'Album successfully deleted';
$a->strings['Album was empty.'] = 'Album was empty.';
$a->strings['a photo'] = 'a photo';
$a->strings['%1$s was tagged in %2$s by %3$s'] = '%1$s was tagged in %2$s by %3$s';
$a->strings['Public access denied.'] = 'Public access denied.';
$a->strings['No photos selected'] = 'No photos selected';
$a->strings['Upload Photos'] = 'Upload photos';
$a->strings['New album name: '] = 'New album name: ';
$a->strings['or select existing album:'] = 'or select existing album:';
$a->strings['Do not show a status post for this upload'] = 'Do not show a status post for this upload';
$a->strings['Permissions'] = 'Permissions';
$a->strings['Do you really want to delete this photo album and all its photos?'] = 'Do you really want to delete this photo album and all its photos?';
$a->strings['Delete Album'] = 'Delete album';
$a->strings['Cancel'] = 'Cancel';
$a->strings['Edit Album'] = 'Edit album';
$a->strings['Drop Album'] = 'Drop album';
$a->strings['Show Newest First'] = 'Show newest first';
$a->strings['Show Oldest First'] = 'Show oldest first';
$a->strings['View Photo'] = 'View photo';
$a->strings['Permission denied. Access to this item may be restricted.'] = 'Permission denied. Access to this item may be restricted.';
$a->strings['Photo not available'] = 'Photo not available';
$a->strings['Do you really want to delete this photo?'] = 'Do you really want to delete this photo?';
$a->strings['Delete Photo'] = 'Delete photo';
$a->strings['View photo'] = 'View photo';
$a->strings['Edit photo'] = 'Edit photo';
$a->strings['Delete photo'] = 'Delete photo';
$a->strings['Use as profile photo'] = 'Use as profile photo';
$a->strings['Private Photo'] = 'Private photo';
$a->strings['View Full Size'] = 'View full size';
$a->strings['Tags: '] = 'Tags: ';
$a->strings['[Select tags to remove]'] = '[Select tags to remove]';
$a->strings['New album name'] = 'New album name';
$a->strings['Caption'] = 'Caption';
$a->strings['Add a Tag'] = 'Add Tag';
$a->strings['Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'] = 'Example: @bob, @jojo@example.com, #California, #camping';
$a->strings['Do not rotate'] = 'Do not rotate';
$a->strings['Rotate CW (right)'] = 'Rotate right (CW)';
$a->strings['Rotate CCW (left)'] = 'Rotate left (CCW)';
$a->strings['This is you'] = 'This is me';
$a->strings['Comment'] = 'Comment';
$a->strings['Preview'] = 'Preview';
$a->strings['Select'] = 'Select';
$a->strings['Delete'] = 'Delete';
$a->strings['Like'] = 'Like';
$a->strings['I like this (toggle)'] = 'I like this (toggle)';
$a->strings['I don\'t like this (toggle)'] = 'I don\'t like this (toggle)';
$a->strings['Map'] = 'Map';
$a->strings['No system theme config value set.'] = 'No system theme configuration value set.';
$a->strings['Delete this item?'] = 'Delete this item?';
$a->strings['toggle mobile'] = 'Toggle mobile';
$a->strings['Method not allowed for this module. Allowed method(s): %s'] = 'Method not allowed for this module. Allowed method(s): %s';
$a->strings['Page not found.'] = 'Page not found';
$a->strings['You must be logged in to use addons. '] = 'You must be logged in to use addons. ';
$a->strings['The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.'] = 'The form security token was incorrect. This probably happened because the form has not been submitted within 3 hours.';
$a->strings['All contacts'] = 'All contacts';
$a->strings['Followers'] = 'Followers';
$a->strings['Following'] = 'Following';
$a->strings['Mutual friends'] = 'Mutual friends';
$a->strings['Could not find any unarchived contact entry for this URL (%s)'] = 'Could not find any unarchived contact entry for this URL (%s)';
$a->strings['The contact entries have been archived'] = 'The contact entries have been archived';
$a->strings['Could not find any contact entry for this URL (%s)'] = 'Could not find any contact entry for this URL (%s)';
$a->strings['The contact has been blocked from the node'] = 'This contact has been blocked from the node';
$a->strings['Post update version number has been set to %s.'] = 'Post update version number has been set to %s.';
$a->strings['Check for pending update actions.'] = 'Check for pending update actions.';
$a->strings['Done.'] = 'Done.';
$a->strings['Execute pending post updates.'] = 'Execute pending post updates.';
$a->strings['All pending post updates are done.'] = 'All pending post updates are done.';
$a->strings['User not found'] = 'User not found';
$a->strings['Enter new password: '] = 'Enter new password: ';
$a->strings['Password update failed. Please try again.'] = 'Password update failed. Please try again.';
$a->strings['Password changed.'] = 'Password changed.';
$a->strings['newer'] = 'Later posts';
$a->strings['older'] = 'Earlier posts';
$a->strings['Frequently'] = 'Frequently';
$a->strings['Hourly'] = 'Hourly';
$a->strings['Twice daily'] = 'Twice daily';
$a->strings['Daily'] = 'Daily';
$a->strings['Weekly'] = 'Weekly';
$a->strings['Monthly'] = 'Monthly';
$a->strings['DFRN'] = 'DFRN';
$a->strings['OStatus'] = 'OStatus';
$a->strings['RSS/Atom'] = 'RSS/Atom';
$a->strings['Email'] = 'Email';
$a->strings['Diaspora'] = 'diaspora*';
$a->strings['Zot!'] = 'Zot!';
$a->strings['LinkedIn'] = 'LinkedIn';
$a->strings['XMPP/IM'] = 'XMPP/IM';
$a->strings['MySpace'] = 'MySpace';
$a->strings['Google+'] = 'Google+';
$a->strings['pump.io'] = 'pump.io';
$a->strings['Twitter'] = 'Twitter';
$a->strings['Discourse'] = 'Discourse';
$a->strings['Diaspora Connector'] = 'diaspora* connector';
$a->strings['GNU Social Connector'] = 'GNU Social Connector';
$a->strings['ActivityPub'] = 'ActivityPub';
$a->strings['pnut'] = 'pnut';
$a->strings['and'] = 'and';
$a->strings['and %d other people'] = 'and %d other people';
$a->strings['<button type="button" %2$s>%1$d person</button> doesn\'t like this'] = [
	0 => '<button type="button" %2$s>%1$d person</button> doesn\'t like this',
	1 => '<button type="button" %2$s>%1$d people</button> don\'t like this',
];
$a->strings['Visible to <strong>everybody</strong>'] = 'Visible to <strong>everybody</strong>';
$a->strings['Please enter a image/video/audio/webpage URL:'] = 'Please enter an image/video/audio/webpage URL:';
$a->strings['Tag term:'] = 'Tag term:';
$a->strings['Save to Folder:'] = 'Save to folder:';
$a->strings['Where are you right now?'] = 'Where are you right now?';
$a->strings['Delete item(s)?'] = 'Delete item(s)?';
$a->strings['New Post'] = 'New post';
$a->strings['Share'] = 'Share';
$a->strings['upload photo'] = 'upload photo';
$a->strings['Attach file'] = 'Attach file';
$a->strings['attach file'] = 'attach file';
$a->strings['Bold'] = 'Bold';
$a->strings['Italic'] = 'Italic';
$a->strings['Underline'] = 'Underline';
$a->strings['Quote'] = 'Quote';
$a->strings['Code'] = 'Code';
$a->strings['Image'] = 'Image';
$a->strings['Link'] = 'Link';
$a->strings['Link or Media'] = 'Link or media';
$a->strings['Set your location'] = 'Set your location';
$a->strings['set location'] = 'set location';
$a->strings['Clear browser location'] = 'Clear browser location';
$a->strings['clear location'] = 'clear location';
$a->strings['Set title'] = 'Set title';
$a->strings['Categories (comma-separated list)'] = 'Categories (comma-separated list)';
$a->strings['Permission settings'] = 'Permission settings';
$a->strings['Public post'] = 'Public post';
$a->strings['Message'] = 'Message';
$a->strings['Browser'] = 'Browser';
$a->strings['Open Compose page'] = 'Open compose page';
$a->strings['remove'] = 'Remove';
$a->strings['Delete Selected Items'] = 'Delete selected items';
$a->strings['You had been addressed (%s).'] = 'You have been addressed (%s).';
$a->strings['%s reshared this.'] = '%s reshared this.';
$a->strings['View %s\'s profile @ %s'] = 'View %s\'s profile @ %s';
$a->strings['Categories:'] = 'Categories:';
$a->strings['Filed under:'] = 'Filed under:';
$a->strings['%s from %s'] = '%s from %s';
$a->strings['View in context'] = 'View in context';
$a->strings['Local Community'] = 'Local community';
$a->strings['Posts from local users on this server'] = 'Posts from local users on this server';
$a->strings['Global Community'] = 'Global community';
$a->strings['Posts from users of the whole federated network'] = 'Posts from users of the whole federated network';
$a->strings['Latest Activity'] = 'Latest activity';
$a->strings['Sort by latest activity'] = 'Sort by latest activity';
$a->strings['Latest Posts'] = 'Latest posts';
$a->strings['Sort by post received date'] = 'Sort by post received date';
$a->strings['Personal'] = 'Personal';
$a->strings['Posts that mention or involve you'] = 'Posts mentioning or involving me';
$a->strings['Starred'] = 'Starred';
$a->strings['Favourite Posts'] = 'My favorite posts';
$a->strings['General Features'] = 'General';
$a->strings['Photo Location'] = 'Photo location';
$a->strings['Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'] = 'Photo metadata is normally removed. This saves the geo tag (if present) and links it to a map prior to removing other metadata.';
$a->strings['Trending Tags'] = 'Trending tags';
$a->strings['Show a community page widget with a list of the most popular tags in recent public posts.'] = 'Show a community page widget with a list of the most popular tags in recent public posts.';
$a->strings['Post Composition Features'] = 'Post composition';
$a->strings['Explicit Mentions'] = 'Explicit Mentions';
$a->strings['Add explicit mentions to comment box for manual control over who gets mentioned in replies.'] = 'Add explicit mentions to comment box for manual control over who gets mentioned in replies.';
$a->strings['Post/Comment Tools'] = 'Post/Comment tools';
$a->strings['Post Categories'] = 'Post categories';
$a->strings['Add categories to your posts'] = 'Add categories to your posts';
$a->strings['Advanced Profile Settings'] = 'Advanced profiles';
$a->strings['Tag Cloud'] = 'Tag cloud';
$a->strings['Provide a personal tag cloud on your profile page'] = 'Provide a personal tag cloud on your profile page';
$a->strings['Display Membership Date'] = 'Display membership date';
$a->strings['Display membership date in profile'] = 'Display membership date in profile';
$a->strings['show more'] = 'show more';
$a->strings['event'] = 'event';
$a->strings['status'] = 'status';
$a->strings['photo'] = 'photo';
$a->strings['%1$s tagged %2$s\'s %3$s with %4$s'] = '%1$s tagged %2$s\'s %3$s with %4$s';
$a->strings['Follow Thread'] = 'Follow thread';
$a->strings['View Status'] = 'View status';
$a->strings['View Profile'] = 'View profile';
$a->strings['View Photos'] = 'View photos';
$a->strings['Network Posts'] = 'Network posts';
$a->strings['View Contact'] = 'View contact';
$a->strings['Send PM'] = 'Send PM';
$a->strings['Block'] = 'Block';
$a->strings['Ignore'] = 'Ignore';
$a->strings['Connect/Follow'] = 'Connect/Follow';
$a->strings['Nothing new here'] = 'Nothing new here';
$a->strings['Go back'] = 'Go back';
$a->strings['Clear notifications'] = 'Clear notifications';
$a->strings['Logout'] = 'Logout';
$a->strings['End this session'] = 'End this session';
$a->strings['Login'] = 'Login';
$a->strings['Sign in'] = 'Sign in';
$a->strings['Profile'] = 'Profile';
$a->strings['Your profile page'] = 'My profile page';
$a->strings['Photos'] = 'Photos';
$a->strings['Your photos'] = 'My photos';
$a->strings['Calendar'] = 'Calendar';
$a->strings['Personal notes'] = 'Personal notes';
$a->strings['Your personal notes'] = 'My personal notes';
$a->strings['Home'] = 'Home';
$a->strings['Home Page'] = 'Home page';
$a->strings['Register'] = 'Sign up now >>';
$a->strings['Create an account'] = 'Create account';
$a->strings['Help'] = 'Help';
$a->strings['Help and documentation'] = 'Help and documentation';
$a->strings['Apps'] = 'Apps';
$a->strings['Addon applications, utilities, games'] = 'Addon applications, utilities, games';
$a->strings['Search'] = 'Search';
$a->strings['Search site content'] = 'Search site content';
$a->strings['Full Text'] = 'Full text';
$a->strings['Tags'] = 'Tags';
$a->strings['Contacts'] = 'Contacts';
$a->strings['Community'] = 'Community';
$a->strings['Conversations on this and other servers'] = 'Conversations on this and other servers';
$a->strings['Directory'] = 'Directory';
$a->strings['People directory'] = 'People directory';
$a->strings['Information'] = 'Information';
$a->strings['Information about this friendica instance'] = 'Information about this Friendica instance';
$a->strings['Terms of Service'] = 'Terms of Service';
$a->strings['Terms of Service of this Friendica instance'] = 'Terms of Service of this Friendica instance';
$a->strings['Network'] = 'Network';
$a->strings['Conversations from your friends'] = 'My friends\' conversations';
$a->strings['Your posts and conversations'] = 'My posts and conversations';
$a->strings['Introductions'] = 'Introductions';
$a->strings['Friend Requests'] = 'Friend requests';
$a->strings['Notifications'] = 'Notifications';
$a->strings['See all notifications'] = 'See all notifications';
$a->strings['Mark as seen'] = 'Mark as seen';
$a->strings['Private mail'] = 'Private messages';
$a->strings['Inbox'] = 'Inbox';
$a->strings['Outbox'] = 'Outbox';
$a->strings['Manage other pages'] = 'Manage other pages';
$a->strings['Settings'] = 'Settings';
$a->strings['Account settings'] = 'Account settings';
$a->strings['Manage/edit friends and contacts'] = 'Manage/Edit friends and contacts';
$a->strings['Admin'] = 'Admin';
$a->strings['Site setup and configuration'] = 'Site setup and configuration';
$a->strings['Navigation'] = 'Navigation';
$a->strings['Site map'] = 'Site map';
$a->strings['Embedding disabled'] = 'Embedding disabled';
$a->strings['Embedded content'] = 'Embedded content';
$a->strings['first'] = 'first';
$a->strings['prev'] = 'prev';
$a->strings['next'] = 'next';
$a->strings['last'] = 'last';
$a->strings['Image/photo'] = 'Image/Photo';
$a->strings['Click to open/close'] = 'Reveal/hide';
$a->strings['$1 wrote:'] = '$1 wrote:';
$a->strings['Encrypted content'] = 'Encrypted content';
$a->strings['Invalid source protocol'] = 'Invalid source protocol';
$a->strings['Invalid link protocol'] = 'Invalid link protocol';
$a->strings['Loading more entries...'] = 'Loading more entries...';
$a->strings['The end'] = 'The end';
$a->strings['Follow'] = 'Follow';
$a->strings['Add New Contact'] = 'Add new contact';
$a->strings['Enter address or web location'] = 'Enter address or web location';
$a->strings['Example: bob@example.com, http://example.com/barbara'] = 'Example: jo@example.com, http://example.com/jo';
$a->strings['Connect'] = 'Connect';
$a->strings['%d invitation available'] = [
	0 => '%d invitation available',
	1 => '%d invitations available',
];
$a->strings['Find People'] = 'Find people';
$a->strings['Enter name or interest'] = 'Enter name or interest';
$a->strings['Examples: Robert Morgenstein, Fishing'] = 'Examples: Robert Morgenstein, fishing';
$a->strings['Find'] = 'Find';
$a->strings['Friend Suggestions'] = 'Friend suggestions';
$a->strings['Similar Interests'] = 'Similar interests';
$a->strings['Random Profile'] = 'Random profile';
$a->strings['Invite Friends'] = 'Invite friends';
$a->strings['Global Directory'] = 'Global directory';
$a->strings['Local Directory'] = 'Local directory';
$a->strings['Relationships'] = 'Relationships';
$a->strings['All Contacts'] = 'All contacts';
$a->strings['Protocols'] = 'Protocols';
$a->strings['All Protocols'] = 'All protocols';
$a->strings['Saved Folders'] = 'Saved Folders';
$a->strings['Everything'] = 'Everything';
$a->strings['Categories'] = 'Categories';
$a->strings['%d contact in common'] = [
	0 => '%d contact in common',
	1 => '%d contacts in common',
];
$a->strings['Archives'] = 'Archives';
$a->strings['Organisations'] = 'Organizations';
$a->strings['News'] = 'News';
$a->strings['Account Types'] = 'Account types';
$a->strings['Export'] = 'Export';
$a->strings['Export calendar as ical'] = 'Export calendar as ical';
$a->strings['Export calendar as csv'] = 'Export calendar as csv';
$a->strings['No contacts'] = 'No contacts';
$a->strings['%d Contact'] = [
	0 => '%d contact',
	1 => '%d contacts',
];
$a->strings['View Contacts'] = 'View contacts';
$a->strings['Remove term'] = 'Remove term';
$a->strings['Saved Searches'] = 'Saved searches';
$a->strings['Trending Tags (last %d hour)'] = [
	0 => 'Trending tags (last %d hour)',
	1 => 'Trending tags (last %d hours)',
];
$a->strings['More Trending Tags'] = 'More trending tags';
$a->strings['XMPP:'] = 'XMPP:';
$a->strings['Location:'] = 'Location:';
$a->strings['Network:'] = 'Network:';
$a->strings['Unfollow'] = 'Unfollow';
$a->strings['Mutuals'] = 'Mutuals';
$a->strings['Post to Email'] = 'Post to email';
$a->strings['Public'] = 'Public';
$a->strings['This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'] = 'This post will be shown to all your followers and can be seen in the community pages and by anyone with its link.';
$a->strings['Limited/Private'] = 'Limited/Private';
$a->strings['This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.'] = 'This post will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere publicly.';
$a->strings['Show to:'] = 'Show to:';
$a->strings['Except to:'] = 'Except to:';
$a->strings['CC: email addresses'] = 'CC: email addresses';
$a->strings['Example: bob@example.com, mary@example.com'] = 'Example: bob@example.com, mary@example.com';
$a->strings['Connectors'] = 'Connectors';
$a->strings['The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'] = 'The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.';
$a->strings['You may need to import the file "database.sql" manually using phpmyadmin or mysql.'] = 'You may need to import the file "database.sql" manually using phpmyadmin or mysql.';
$a->strings['Could not find a command line version of PHP in the web server PATH.'] = 'Could not find a command line version of PHP in the web server PATH.';
$a->strings['PHP executable path'] = 'PHP executable path';
$a->strings['Enter full path to php executable. You can leave this blank to continue the installation.'] = 'Enter full path to php executable. You can leave this blank to continue the installation.';
$a->strings['Command line PHP'] = 'Command line PHP';
$a->strings['PHP executable is not the php cli binary (could be cgi-fgci version)'] = 'PHP executable is not a php cli binary; it could possibly be a cgi-fgci version.';
$a->strings['Found PHP version: '] = 'Found PHP version: ';
$a->strings['PHP cli binary'] = 'PHP cli binary';
$a->strings['The command line version of PHP on your system does not have "register_argc_argv" enabled.'] = 'The command line version of PHP on your system does not have "register_argc_argv" enabled.';
$a->strings['This is required for message delivery to work.'] = 'This is required for message delivery to work.';
$a->strings['PHP register_argc_argv'] = 'PHP register_argc_argv';
$a->strings['Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'] = 'Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys';
$a->strings['If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".'] = 'If running under Windows OS, please see "http://www.php.net/manual/en/openssl.installation.php".';
$a->strings['Generate encryption keys'] = 'Generate encryption keys';
$a->strings['Error: Apache webserver mod-rewrite module is required but not installed.'] = 'Error: Apache web server mod-rewrite module is required but not installed.';
$a->strings['Apache mod_rewrite module'] = 'Apache mod_rewrite module';
$a->strings['Error: PDO or MySQLi PHP module required but not installed.'] = 'Error: PDO or MySQLi PHP module required but not installed.';
$a->strings['Error: The MySQL driver for PDO is not installed.'] = 'Error: MySQL driver for PDO is not installed.';
$a->strings['PDO or MySQLi PHP module'] = 'PDO or MySQLi PHP module';
$a->strings['Error, XML PHP module required but not installed.'] = 'Error, XML PHP module required but not installed.';
$a->strings['XML PHP module'] = 'XML PHP module';
$a->strings['libCurl PHP module'] = 'libCurl PHP module';
$a->strings['Error: libCURL PHP module required but not installed.'] = 'Error: libCURL PHP module required but not installed.';
$a->strings['GD graphics PHP module'] = 'GD graphics PHP module';
$a->strings['Error: GD graphics PHP module with JPEG support required but not installed.'] = 'Error: GD graphics PHP module with JPEG support required but not installed.';
$a->strings['OpenSSL PHP module'] = 'OpenSSL PHP module';
$a->strings['Error: openssl PHP module required but not installed.'] = 'Error: openssl PHP module required but not installed.';
$a->strings['mb_string PHP module'] = 'mb_string PHP module';
$a->strings['Error: mb_string PHP module required but not installed.'] = 'Error: mb_string PHP module required but not installed.';
$a->strings['iconv PHP module'] = 'iconv PHP module';
$a->strings['Error: iconv PHP module required but not installed.'] = 'Error: iconv PHP module required but not installed.';
$a->strings['POSIX PHP module'] = 'POSIX PHP module';
$a->strings['Error: POSIX PHP module required but not installed.'] = 'Error: POSIX PHP module required but not installed.';
$a->strings['JSON PHP module'] = 'JSON PHP module';
$a->strings['Error: JSON PHP module required but not installed.'] = 'Error: JSON PHP module is required but not installed.';
$a->strings['File Information PHP module'] = 'File Information PHP module';
$a->strings['Error: File Information PHP module required but not installed.'] = 'Error: File Information PHP module required but not installed.';
$a->strings['The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server and it is unable to do so.'] = 'The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server, but is unable to do so.';
$a->strings['This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.'] = 'This is most often a permission setting issue, as the web server may not be able to write files in your directory - even if you can.';
$a->strings['At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.'] = 'At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.';
$a->strings['config/local.config.php is writable'] = 'config/local.config.php is writable';
$a->strings['Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.'] = 'Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.';
$a->strings['In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.'] = 'In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top-level directory.';
$a->strings['Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.'] = 'Please ensure the user that your web server runs as (e.g. www-data) has write access to this directory.';
$a->strings['Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files (.tpl) that it contains.'] = 'Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files (.tpl) that it contains.';
$a->strings['view/smarty3 is writable'] = 'view/smarty3 is writable';
$a->strings['Url rewrite in .htaccess seems not working. Make sure you copied .htaccess-dist to .htaccess.'] = 'Url rewrite in .htaccess does not seem to work. Make sure you copied .htaccess-dist to .htaccess.';
$a->strings['Error message from Curl when fetching'] = 'Error message from Curl while fetching';
$a->strings['Url rewrite is working'] = 'URL rewrite is working';
$a->strings['The detection of TLS to secure the communication between the browser and the new Friendica server failed.'] = 'Failed to detect TLS that secures the communication between the browser and the new Friendica server.';
$a->strings['ImageMagick PHP extension is not installed'] = 'ImageMagick PHP extension is not installed';
$a->strings['ImageMagick PHP extension is installed'] = 'ImageMagick PHP extension is installed';
$a->strings['ImageMagick supports GIF'] = 'ImageMagick supports GIF';
$a->strings['Database already in use.'] = 'Database already in use.';
$a->strings['Could not connect to database.'] = 'Could not connect to database.';
$a->strings['Monday'] = 'Monday';
$a->strings['Tuesday'] = 'Tuesday';
$a->strings['Wednesday'] = 'Wednesday';
$a->strings['Thursday'] = 'Thursday';
$a->strings['Friday'] = 'Friday';
$a->strings['Saturday'] = 'Saturday';
$a->strings['Sunday'] = 'Sunday';
$a->strings['January'] = 'January';
$a->strings['February'] = 'February';
$a->strings['March'] = 'March';
$a->strings['April'] = 'April';
$a->strings['May'] = 'May';
$a->strings['June'] = 'June';
$a->strings['July'] = 'July';
$a->strings['August'] = 'August';
$a->strings['September'] = 'September';
$a->strings['October'] = 'October';
$a->strings['November'] = 'November';
$a->strings['December'] = 'December';
$a->strings['Mon'] = 'Mon';
$a->strings['Tue'] = 'Tue';
$a->strings['Wed'] = 'Wed';
$a->strings['Thu'] = 'Thu';
$a->strings['Fri'] = 'Fri';
$a->strings['Sat'] = 'Sat';
$a->strings['Sun'] = 'Sun';
$a->strings['Jan'] = 'Jan';
$a->strings['Feb'] = 'Feb';
$a->strings['Mar'] = 'Mar';
$a->strings['Apr'] = 'Apr';
$a->strings['Jun'] = 'Jun';
$a->strings['Jul'] = 'Jul';
$a->strings['Aug'] = 'Aug';
$a->strings['Sep'] = 'Sep';
$a->strings['Oct'] = 'Oct';
$a->strings['Nov'] = 'Nov';
$a->strings['Dec'] = 'Dec';
$a->strings['The logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'The logfile \'%s\' is not usable. No logging is possible (error: \'%s\')';
$a->strings['The debug logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'The debug logfile \'%s\' is not usable. No logging is possible (error: \'%s\')';
$a->strings['Storage base path'] = 'Storage base path';
$a->strings['Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'] = 'Folder where uploaded files are saved. For maximum security, this should be a path outside web server folder tree';
$a->strings['Enter a valid existing folder'] = 'Enter a valid existing folder';
$a->strings['Update %s failed. See error logs.'] = 'Update %s failed. See error logs.';
$a->strings['
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can\'t do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.'] = '
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can\'t do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.';
$a->strings['[Friendica Notify] Database update'] = '[Friendica Notify] Database update';
$a->strings['The database version had been set to %s.'] = 'The database version has been set to %s.';
$a->strings['
Error %d occurred during database update:
%s
'] = '
Error %d occurred during database update:
%s
';
$a->strings['Errors encountered performing database changes: '] = 'Errors encountered performing database changes: ';
$a->strings['%s: Database update'] = '%s: Database update';
$a->strings['%s: updating %s table.'] = '%s: updating %s table.';
$a->strings['Unauthorized'] = 'Unauthorized';
$a->strings['Internal Server Error'] = 'Internal Server Error';
$a->strings['Legacy module file not found: %s'] = 'Legacy module file not found: %s';
$a->strings['Everybody'] = 'Everybody';
$a->strings['edit'] = 'edit';
$a->strings['add'] = 'add';
$a->strings['Approve'] = 'Approve';
$a->strings['Organisation'] = 'Organization';
$a->strings['Disallowed profile URL.'] = 'Disallowed profile URL.';
$a->strings['Blocked domain'] = 'Blocked domain';
$a->strings['Connect URL missing.'] = 'Connect URL missing.';
$a->strings['The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.'] = 'The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.';
$a->strings['The profile address specified does not provide adequate information.'] = 'The profile address specified does not provide adequate information.';
$a->strings['No compatible communication protocols or feeds were discovered.'] = 'No compatible communication protocols or feeds were discovered.';
$a->strings['An author or name was not found.'] = 'An author or name was not found.';
$a->strings['No browser URL could be matched to this address.'] = 'No browser URL could be matched to this address.';
$a->strings['Unable to match @-style Identity Address with a known protocol or email contact.'] = 'Unable to match @-style identity address with a known protocol or email contact.';
$a->strings['Use mailto: in front of address to force email check.'] = 'Use mailto: in front of address to force email check.';
$a->strings['The profile address specified belongs to a network which has been disabled on this site.'] = 'The profile address specified belongs to a network which has been disabled on this site.';
$a->strings['Limited profile. This person will be unable to receive direct/personal notifications from you.'] = 'Limited profile: This person will be unable to receive direct/private messages from you.';
$a->strings['Unable to retrieve contact information.'] = 'Unable to retrieve contact information.';
$a->strings['Starts:'] = 'Starts:';
$a->strings['Finishes:'] = 'Finishes:';
$a->strings['all-day'] = 'All-day';
$a->strings['Sept'] = 'Sep';
$a->strings['today'] = 'today';
$a->strings['month'] = 'month';
$a->strings['week'] = 'week';
$a->strings['day'] = 'day';
$a->strings['No events to display'] = 'No events to display';
$a->strings['Access to this profile has been restricted.'] = 'Access to this profile has been restricted.';
$a->strings['l, F j'] = 'l, F j';
$a->strings['Edit event'] = 'Edit event';
$a->strings['Duplicate event'] = 'Duplicate event';
$a->strings['Delete event'] = 'Delete event';
$a->strings['l F d, Y \@ g:i A'] = 'l F d, Y \@ g:i A';
$a->strings['D g:i A'] = 'D g:i A';
$a->strings['g:i A'] = 'g:i A';
$a->strings['Show map'] = 'Show map';
$a->strings['Hide map'] = 'Hide map';
$a->strings['%s\'s birthday'] = '%s\'s birthday';
$a->strings['Happy Birthday %s'] = 'Happy Birthday, %s!';
$a->strings['activity'] = 'activity';
$a->strings['post'] = 'post';
$a->strings['Content warning: %s'] = 'Content warning: %s';
$a->strings['bytes'] = 'bytes';
$a->strings['View on separate page'] = 'View on separate page';
$a->strings['[no subject]'] = '[no subject]';
$a->strings['Wall Photos'] = 'Wall photos';
$a->strings['Edit profile'] = 'Edit profile';
$a->strings['Change profile photo'] = 'Change profile photo';
$a->strings['Homepage:'] = 'Homepage:';
$a->strings['About:'] = 'About:';
$a->strings['Atom feed'] = 'Atom feed';
$a->strings['F d'] = 'F d';
$a->strings['[today]'] = '[today]';
$a->strings['Birthday Reminders'] = 'Birthday reminders';
$a->strings['Birthdays this week:'] = 'Birthdays this week:';
$a->strings['g A l F d'] = 'g A l F d';
$a->strings['[No description]'] = '[No description]';
$a->strings['Event Reminders'] = 'Event reminders';
$a->strings['Upcoming events the next 7 days:'] = 'Upcoming events the next 7 days:';
$a->strings['OpenWebAuth: %1$s welcomes %2$s'] = 'OpenWebAuth: %1$s welcomes %2$s';
$a->strings['Hometown:'] = 'Home town:';
$a->strings['Sexual Preference:'] = 'Sexual preference:';
$a->strings['Political Views:'] = 'Political views:';
$a->strings['Religious Views:'] = 'Religious views:';
$a->strings['Likes:'] = 'Likes:';
$a->strings['Dislikes:'] = 'Dislikes:';
$a->strings['Title/Description:'] = 'Title/Description:';
$a->strings['Summary'] = 'Summary';
$a->strings['Musical interests'] = 'Music:';
$a->strings['Books, literature'] = 'Books, literature, poetry:';
$a->strings['Television'] = 'Television:';
$a->strings['Film/dance/culture/entertainment'] = 'Film, dance, culture, entertainment';
$a->strings['Hobbies/Interests'] = 'Hobbies/Interests:';
$a->strings['Love/romance'] = 'Love/Romance:';
$a->strings['Work/employment'] = 'Work/Employment:';
$a->strings['School/education'] = 'School/Education:';
$a->strings['Contact information and Social Networks'] = 'Contact information and other social networks:';
$a->strings['SERIOUS ERROR: Generation of security keys failed.'] = 'SERIOUS ERROR: Generation of security keys failed.';
$a->strings['Login failed'] = 'Login failed';
$a->strings['Not enough information to authenticate'] = 'Not enough information to authenticate';
$a->strings['Password can\'t be empty'] = 'Password can\'t be empty';
$a->strings['Empty passwords are not allowed.'] = 'Empty passwords are not allowed.';
$a->strings['The new password has been exposed in a public data dump, please choose another.'] = 'The new password has been exposed in a public data dump; please choose another.';
$a->strings['Passwords do not match. Password unchanged.'] = 'Passwords do not match. Password unchanged.';
$a->strings['An invitation is required.'] = 'An invitation is required.';
$a->strings['Invitation could not be verified.'] = 'Invitation could not be verified.';
$a->strings['Invalid OpenID url'] = 'Invalid OpenID URL';
$a->strings['We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.'] = 'We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.';
$a->strings['The error message was:'] = 'The error message was:';
$a->strings['Please enter the required information.'] = 'Please enter the required information.';
$a->strings['system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.'] = 'system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.';
$a->strings['Username should be at least %s character.'] = [
	0 => 'Username should be at least %s character.',
	1 => 'Username should be at least %s characters.',
];
$a->strings['Username should be at most %s character.'] = [
	0 => 'Username should be at most %s character.',
	1 => 'Username should be at most %s characters.',
];
$a->strings['That doesn\'t appear to be your full (First Last) name.'] = 'That doesn\'t appear to be your full (i.e first and last) name.';
$a->strings['Your email domain is not among those allowed on this site.'] = 'Your email domain is not allowed on this site.';
$a->strings['Not a valid email address.'] = 'Not a valid email address.';
$a->strings['The nickname was blocked from registration by the nodes admin.'] = 'The nickname was blocked from registration by the nodes admin.';
$a->strings['Cannot use that email.'] = 'Cannot use that email.';
$a->strings['Your nickname can only contain a-z, 0-9 and _.'] = 'Your nickname can only contain a-z, 0-9 and _.';
$a->strings['Nickname is already registered. Please choose another.'] = 'Nickname is already registered. Please choose another.';
$a->strings['An error occurred during registration. Please try again.'] = 'An error occurred during registration. Please try again.';
$a->strings['An error occurred creating your default profile. Please try again.'] = 'An error occurred creating your default profile. Please try again.';
$a->strings['An error occurred creating your self contact. Please try again.'] = 'An error occurred creating your self contact. Please try again.';
$a->strings['Friends'] = 'Friends';
$a->strings['Profile Photos'] = 'Profile photos';
$a->strings['Registration details for %s'] = 'Registration details for %s';
$a->strings['
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		'] = '
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		';
$a->strings['Registration at %s'] = 'Registration at %s';
$a->strings['
				Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
			'] = '
				Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
			';
$a->strings['Addon not found.'] = 'Addon not found.';
$a->strings['Addon %s disabled.'] = 'Addon %s disabled.';
$a->strings['Addon %s enabled.'] = 'Addon %s enabled.';
$a->strings['Disable'] = 'Disable';
$a->strings['Enable'] = 'Enable';
$a->strings['Administration'] = 'Administration';
$a->strings['Addons'] = 'Addons';
$a->strings['Toggle'] = 'Toggle';
$a->strings['Author: '] = 'Author: ';
$a->strings['Maintainer: '] = 'Maintainer: ';
$a->strings['Addon %s failed to install.'] = 'Addon %s failed to install.';
$a->strings['Save Settings'] = 'Save settings';
$a->strings['Reload active addons'] = 'Reload active addons';
$a->strings['There are currently no addons available on your node. You can find the official addon repository at %1$s and might find other interesting addons in the open addon registry at %2$s'] = 'There are currently no addons available on your node. You can find the official addon repository at %1$s and might find other interesting addons in the open addon registry at %2$s';
$a->strings['Update has been marked successful'] = 'Update has been marked successful';
$a->strings['Database structure update %s was successfully applied.'] = 'Database structure update %s was successfully applied.';
$a->strings['Executing of database structure update %s failed with error: %s'] = 'Execution of database structure update %s failed with error: %s';
$a->strings['Executing %s failed with error: %s'] = 'Execution of %s failed with error: %s';
$a->strings['Update %s was successfully applied.'] = 'Update %s was successfully applied.';
$a->strings['Update %s did not return a status. Unknown if it succeeded.'] = 'Update %s did not return a status. Unknown if it succeeded.';
$a->strings['There was no additional update function %s that needed to be called.'] = 'There was no additional update function %s that needed to be called.';
$a->strings['No failed updates.'] = 'No failed updates.';
$a->strings['Check database structure'] = 'Check database structure';
$a->strings['Failed Updates'] = 'Failed updates';
$a->strings['This does not include updates prior to 1139, which did not return a status.'] = 'This does not include updates prior to 1139, which did not return a status.';
$a->strings['Mark success (if update was manually applied)'] = 'Mark success (if update was manually applied)';
$a->strings['Attempt to execute this update step automatically'] = 'Attempt to execute this update step automatically';
$a->strings['Lock feature %s'] = 'Lock feature %s';
$a->strings['Manage Additional Features'] = 'Manage additional features';
$a->strings['Other'] = 'Other';
$a->strings['unknown'] = 'unknown';
$a->strings['This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.'] = 'This page offers statistics about the federated social network, of which your Friendica node is one part. These numbers do not represent the entire network, but merely the parts that are connected to your node."';
$a->strings['Federation Statistics'] = 'Federation statistics';
$a->strings['The logfile \'%s\' is not writable. No logging possible'] = 'The logfile \'%s\' is not writable. No logging is possible';
$a->strings['PHP log currently enabled.'] = 'PHP log currently enabled.';
$a->strings['PHP log currently disabled.'] = 'PHP log currently disabled.';
$a->strings['Logs'] = 'Logs';
$a->strings['Clear'] = 'Clear';
$a->strings['Enable Debugging'] = 'Enable debugging';
$a->strings['Log file'] = 'Log file';
$a->strings['Must be writable by web server. Relative to your Friendica top-level directory.'] = 'Must be writable by web server and relative to your Friendica top-level directory.';
$a->strings['Log level'] = 'Log level';
$a->strings['PHP logging'] = 'PHP logging';
$a->strings['To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the \'error_log\' line is relative to the friendica top-level directory and must be writeable by the web server. The option \'1\' for \'log_errors\' and \'display_errors\' is to enable these options, set to \'0\' to disable them.'] = 'To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the \'error_log\' line is relative to the friendica top-level directory and must be writeable by the web server. The option \'1\' for \'log_errors\' and \'display_errors\' is to enable these options, set to \'0\' to disable them.';
$a->strings['View Logs'] = 'View logs';
$a->strings['Show all'] = 'Show all';
$a->strings['Event details'] = 'Event details';
$a->strings['Inspect Deferred Worker Queue'] = 'Inspect deferred worker queue';
$a->strings['This page lists the deferred worker jobs. This are jobs that couldn\'t be executed at the first time.'] = 'This page lists the deferred worker jobs. These are jobs that couldn\'t initially be executed.';
$a->strings['Inspect Worker Queue'] = 'Inspect worker queue';
$a->strings['This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.'] = 'This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.';
$a->strings['ID'] = 'ID';
$a->strings['Job Parameters'] = 'Job parameters';
$a->strings['Created'] = 'Created';
$a->strings['Priority'] = 'Priority';
$a->strings['No special theme for mobile devices'] = 'No special theme for mobile devices';
$a->strings['%s - (Experimental)'] = '%s - (Experimental)';
$a->strings['No community page'] = 'No community page';
$a->strings['Public postings from users of this site'] = 'Public postings from users of this site';
$a->strings['Public postings from the federated network'] = 'Public postings from the federated network';
$a->strings['Public postings from local users and the federated network'] = 'Public postings from local users and the federated network';
$a->strings['Multi user instance'] = 'Multi user instance';
$a->strings['Closed'] = 'Closed';
$a->strings['Requires approval'] = 'Requires approval';
$a->strings['Open'] = 'Open';
$a->strings['Don\'t check'] = 'Don\'t check';
$a->strings['check the stable version'] = 'check for stable version updates';
$a->strings['check the development version'] = 'check for development version updates';
$a->strings['Site'] = 'Site';
$a->strings['Republish users to directory'] = 'Republish users to directory';
$a->strings['Registration'] = 'Registration';
$a->strings['File upload'] = 'File upload';
$a->strings['Policies'] = 'Policies';
$a->strings['Advanced'] = 'Advanced';
$a->strings['Auto Discovered Contact Directory'] = 'Auto-discovered contact directory';
$a->strings['Performance'] = 'Performance';
$a->strings['Worker'] = 'Worker';
$a->strings['Message Relay'] = 'Message relay';
$a->strings['Site name'] = 'Site name';
$a->strings['Sender Email'] = 'Sender email';
$a->strings['The email address your server shall use to send notification emails from.'] = 'The email address your server shall use to send notification emails from.';
$a->strings['Banner/Logo'] = 'Banner/Logo';
$a->strings['Shortcut icon'] = 'Shortcut icon';
$a->strings['Link to an icon that will be used for browsers.'] = 'Link to an icon that will be used for browsers.';
$a->strings['Touch icon'] = 'Touch icon';
$a->strings['Link to an icon that will be used for tablets and mobiles.'] = 'Link to an icon that will be used for tablets and mobiles.';
$a->strings['Additional Info'] = 'Additional Info';
$a->strings['For public servers: you can add additional information here that will be listed at %s/servers.'] = 'For public servers: You can add additional information here that will be listed at %s/servers.';
$a->strings['System language'] = 'System language';
$a->strings['System theme'] = 'System theme';
$a->strings['Mobile system theme'] = 'Mobile system theme';
$a->strings['Theme for mobile devices'] = 'Theme for mobile devices';
$a->strings['Force SSL'] = 'Force SSL';
$a->strings['Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.'] = 'Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.';
$a->strings['Single user instance'] = 'Single user instance';
$a->strings['Make this instance multi-user or single-user for the named user'] = 'Make this instance multi-user or single-user for the named user';
$a->strings['Maximum image size'] = 'Maximum image size';
$a->strings['Maximum image length'] = 'Maximum image length';
$a->strings['Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.'] = 'Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.';
$a->strings['JPEG image quality'] = 'JPEG image quality';
$a->strings['Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.'] = 'Uploaded JPEGs will be saved at this quality setting [0-100]. Default is 100, which is the original quality level.';
$a->strings['Register policy'] = 'Registration policy';
$a->strings['Maximum Daily Registrations'] = 'Maximum daily registrations';
$a->strings['If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.'] = 'If open registration is permitted, this sets the maximum number of new registrations per day.  This setting has no effect for registrations by approval.';
$a->strings['Register text'] = 'Registration text';
$a->strings['Will be displayed prominently on the registration page. You can use BBCode here.'] = 'Will be displayed prominently on the registration page. You may use BBCode here.';
$a->strings['Forbidden Nicknames'] = 'Forbidden Nicknames';
$a->strings['Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.'] = 'Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.';
$a->strings['Accounts abandoned after x days'] = 'Accounts abandoned after so many days';
$a->strings['Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.'] = 'Will not waste system resources polling external sites for abandoned accounts. Enter 0 for no time limit.';
$a->strings['Allowed friend domains'] = 'Allowed friend domains';
$a->strings['Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains'] = 'Comma-separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Leave empty to allow any domains';
$a->strings['Allowed email domains'] = 'Allowed email domains';
$a->strings['Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains'] = 'Comma-separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Leave empty to allow any domains';
$a->strings['No OEmbed rich content'] = 'No OEmbed rich content';
$a->strings['Don\'t show the rich content (e.g. embedded PDF), except from the domains listed below.'] = 'Don\'t show rich content (e.g. embedded PDF), except from the domains listed below.';
$a->strings['Block public'] = 'Block public';
$a->strings['Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.'] = 'Block public access to all otherwise public personal pages on this site, except for local users when logged in.';
$a->strings['Force publish'] = 'Mandatory directory listing';
$a->strings['Check to force all profiles on this site to be listed in the site directory.'] = 'Force all profiles on this site to be listed in the site directory.';
$a->strings['Enabling this may violate privacy laws like the GDPR'] = 'Enabling this may violate privacy laws like the GDPR';
$a->strings['Global directory URL'] = 'Global directory URL';
$a->strings['URL to the global directory. If this is not set, the global directory is completely unavailable to the application.'] = 'URL to the global directory: If this is not set, the global directory is completely unavailable to the application.';
$a->strings['Private posts by default for new users'] = 'Private posts by default for new users';
$a->strings['Don\'t include post content in email notifications'] = 'Don\'t include post content in email notifications';
$a->strings['Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.'] = 'Don\'t include the content of a post/comment/private message in the email notifications sent from this site, as a privacy measure.';
$a->strings['Disallow public access to addons listed in the apps menu.'] = 'Disallow public access to addons listed in the apps menu.';
$a->strings['Checking this box will restrict addons listed in the apps menu to members only.'] = 'Checking this box will restrict addons listed in the apps menu to members only.';
$a->strings['Don\'t embed private images in posts'] = 'Don\'t embed private images in posts';
$a->strings['Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.'] = 'Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.';
$a->strings['Explicit Content'] = 'Explicit Content';
$a->strings['Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.'] = 'Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.';
$a->strings['Allow Users to set remote_self'] = 'Allow users to set "Remote self"';
$a->strings['With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.'] = 'This allows every user to mark contacts as a "Remote self" in the repair contact dialogue. Setting this flag on a contact will mirror every posting of that contact in the users stream.';
$a->strings['Community pages for visitors'] = 'Community pages for visitors';
$a->strings['Which community pages should be available for visitors. Local users always see both pages.'] = 'Which community pages should be available for visitors. Local users always see both pages.';
$a->strings['Posts per user on community page'] = 'Posts per user on community page';
$a->strings['The maximum number of posts per user on the community page. (Not valid for "Global Community")'] = 'The maximum number of posts per user on the community page. (Not valid for "Global Community")';
$a->strings['Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'] = 'diaspora* support can\'t be enabled because Friendica was installed into a sub directory.';
$a->strings['Enable Diaspora support'] = 'Enable diaspora* support';
$a->strings['Verify SSL'] = 'Verify SSL';
$a->strings['If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.'] = 'If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.';
$a->strings['Proxy user'] = 'Proxy user';
$a->strings['Proxy URL'] = 'Proxy URL';
$a->strings['Network timeout'] = 'Network timeout';
$a->strings['Value is in seconds. Set to 0 for unlimited (not recommended).'] = 'Value is in seconds. Set to 0 for unlimited (not recommended).';
$a->strings['Maximum Load Average'] = 'Maximum load average';
$a->strings['Maximum system load before delivery and poll processes are deferred - default %d.'] = 'Maximum system load before delivery and poll processes are deferred - default %d.';
$a->strings['Minimal Memory'] = 'Minimal memory';
$a->strings['Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).'] = 'Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).';
$a->strings['Days between requery'] = 'Days between enquiry';
$a->strings['Number of days after which a server is requeried for his contacts.'] = 'Number of days after which a server is rechecked for contacts.';
$a->strings['Discover contacts from other servers'] = 'Discover contacts from other servers';
$a->strings['Search the local directory'] = 'Search the local directory';
$a->strings['Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.'] = 'Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.';
$a->strings['Publish server information'] = 'Publish server information';
$a->strings['If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.'] = 'If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.';
$a->strings['Check upstream version'] = 'Check upstream version';
$a->strings['Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'] = 'Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.';
$a->strings['Suppress Tags'] = 'Suppress tags';
$a->strings['Suppress showing a list of hashtags at the end of the posting.'] = 'Suppress listed hashtags at the end of posts.';
$a->strings['Clean database'] = 'Clean database';
$a->strings['Remove old remote items, orphaned database records and old content from some other helper tables.'] = 'Remove old remote items, orphaned database records, and old content from some other helper tables.';
$a->strings['Lifespan of remote items'] = 'Lifespan of remote items';
$a->strings['When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.'] = 'If the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items, are always kept. 0 disables this behavior.';
$a->strings['Lifespan of unclaimed items'] = 'Lifespan of unclaimed items';
$a->strings['When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.'] = 'If the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.';
$a->strings['Lifespan of raw conversation data'] = 'Lifespan of raw conversation data';
$a->strings['The conversation data is used for ActivityPub and OStatus, as well as for debug purposes. It should be safe to remove it after 14 days, default is 90 days.'] = 'The conversation data is used for ActivityPub and OStatus, as well as for debug purposes. It should be safe to remove it after 14 days, default is 90 days.';
$a->strings['Maximum numbers of comments per post'] = 'Maximum number of comments per post';
$a->strings['How much comments should be shown for each post? Default value is 100.'] = 'How many comments should be shown for each post? (Default 100)';
$a->strings['Temp path'] = 'Temp path';
$a->strings['If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.'] = 'Enter a different temp path if your system restricts the webserver\'s access to the system temp path.';
$a->strings['Only search in tags'] = 'Only search in tags';
$a->strings['On large systems the text search can slow down the system extremely.'] = 'On large systems, the text search can slow down the system significantly.';
$a->strings['Maximum number of parallel workers'] = 'Maximum number of parallel workers';
$a->strings['On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.'] = 'On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.';
$a->strings['Enable fastlane'] = 'Enable fast-lane';
$a->strings['When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.'] = 'When enabled, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.';
$a->strings['Direct relay transfer'] = 'Direct relay transfer';
$a->strings['Enables the direct transfer to other servers without using the relay servers'] = 'Enables direct transfer to other servers without using a relay server.';
$a->strings['Relay scope'] = 'Relay scope';
$a->strings['Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.'] = 'Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.';
$a->strings['Disabled'] = 'Disabled';
$a->strings['all'] = 'all';
$a->strings['tags'] = 'tags';
$a->strings['Server tags'] = 'Server tags';
$a->strings['Comma separated list of tags for the "tags" subscription.'] = 'Comma separated list of tags for the "tags" subscription.';
$a->strings['Deny Server tags'] = 'Deny server tags';
$a->strings['Allow user tags'] = 'Allow user tags';
$a->strings['If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".'] = 'If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".';
$a->strings['Start Relocation'] = 'Start relocation';
$a->strings['Invalid storage backend setting value.'] = 'Invalid storage backend setting.';
$a->strings['Database (legacy)'] = 'Database (legacy)';
$a->strings['Your DB still runs with MyISAM tables. You should change the engine type to InnoDB. As Friendica will use InnoDB only features in the future, you should change this! See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Your DB still runs with MyISAM tables. You should change the engine type to InnoDB. As Friendica will use InnoDB-only features in the future, you should change this! See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />';
$a->strings['There is a new version of Friendica available for download. Your current version is %1$s, upstream version is %2$s'] = 'A new Friendica version is available now. Your current version is %1$s, upstream version is %2$s';
$a->strings['The database update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear.'] = 'The database update failed. Please run "php bin/console.php dbstructure update" from the command line and check for errors that may appear.';
$a->strings['The last update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear. (Some of the errors are possibly inside the logfile.)'] = 'The last update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that may appear in the console and logfile output.';
$a->strings['The worker was never executed. Please check your database structure!'] = 'The worker process has never been executed. Please check your database structure!';
$a->strings['The last worker execution was on %s UTC. This is older than one hour. Please check your crontab settings.'] = 'The last worker process started at %s UTC. This is more than one hour ago. Please adjust your crontab settings.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>.htconfig.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Friendica\'s configuration is now stored in config/local.config.php; please copy config/local-sample.config.php and move your config from config/local.ini.php. See the Config help page for help with the transition.<code>.htconfig.php</code>. See <a href="%s">the Config help page</a> for help with the transition.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>config/local.ini.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Friendica\'s configuration is now stored in config/local.config.php; please copy config/local-sample.config.php and move your config from <code>config/local.ini.php</code>. See <a href="%s">the Config help page</a> for help with the transition.';
$a->strings['<a href="%s">%s</a> is not reachable on your system. This is a severe configuration issue that prevents server to server communication. See <a href="%s">the installation page</a> for help.'] = '<a href="%s">%s</a> is not reachable on your system. This is a severe configuration issue that prevents server to server communication. See <a href="%s">the installation page</a> for help.';
$a->strings['Friendica\'s system.basepath was updated from \'%s\' to \'%s\'. Please remove the system.basepath from your db to avoid differences.'] = 'The system.basepath was updated from \'%s\' to \'%s\'. Please remove the system.basepath from your db to avoid differences.';
$a->strings['Friendica\'s current system.basepath \'%s\' is wrong and the config file \'%s\' isn\'t used.'] = 'The current system.basepath \'%s\' is wrong and the config file \'%s\' isn\'t used.';
$a->strings['Friendica\'s current system.basepath \'%s\' is not equal to the config file \'%s\'. Please fix your configuration.'] = 'The current system.basepath \'%s\' is not equal to the config file \'%s\'. Please fix your configuration.';
$a->strings['Message queues'] = 'Message queues';
$a->strings['Server Settings'] = 'Server Settings';
$a->strings['Version'] = 'Version';
$a->strings['Active addons'] = 'Active addons';
$a->strings['Theme %s disabled.'] = 'Theme %s disabled.';
$a->strings['Theme %s successfully enabled.'] = 'Theme %s successfully enabled.';
$a->strings['Theme %s failed to install.'] = 'Theme %s failed to install.';
$a->strings['Screenshot'] = 'Screenshot';
$a->strings['Themes'] = 'Theme selection';
$a->strings['Unknown theme.'] = 'Unknown theme.';
$a->strings['Reload active themes'] = 'Reload active themes';
$a->strings['No themes found on the system. They should be placed in %1$s'] = 'No themes found on the system. They should be placed in %1$s';
$a->strings['[Experimental]'] = '[Experimental]';
$a->strings['[Unsupported]'] = '[Unsupported]';
$a->strings['Display Terms of Service'] = 'Display Terms of Service';
$a->strings['Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.'] = 'Enable the Terms of Service page. If this is enabled, a link to the terms will be added to the registration form and to the general information page.';
$a->strings['Display Privacy Statement'] = 'Display Privacy Statement';
$a->strings['Privacy Statement Preview'] = 'Privacy Statement Preview';
$a->strings['The Terms of Service'] = 'Terms of Service';
$a->strings['Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.'] = 'Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] or less.';
$a->strings['Contact not found'] = 'Contact not found';
$a->strings['No installed applications.'] = 'No installed applications.';
$a->strings['Applications'] = 'Applications';
$a->strings['Item was not found.'] = 'Item was not found.';
$a->strings['Please login to continue.'] = 'Please login to continue.';
$a->strings['Overview'] = 'Overview';
$a->strings['Configuration'] = 'Configuration';
$a->strings['Additional features'] = 'Additional features';
$a->strings['Database'] = 'Database';
$a->strings['DB updates'] = 'DB updates';
$a->strings['Inspect Deferred Workers'] = 'Inspect deferred workers';
$a->strings['Inspect worker Queue'] = 'Inspect worker queue';
$a->strings['Diagnostics'] = 'Diagnostics';
$a->strings['PHP Info'] = 'PHP info';
$a->strings['probe address'] = 'Probe address';
$a->strings['check webfinger'] = 'check WebFinger';
$a->strings['Babel'] = 'Babel';
$a->strings['Addon Features'] = 'Addon features';
$a->strings['User registrations waiting for confirmation'] = 'User registrations awaiting confirmation';
$a->strings['Daily posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Daily posting limit of %d post reached. The post was rejected.',
	1 => 'Daily posting limit of %d posts reached. This post was rejected.',
];
$a->strings['Weekly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Weekly posting limit of %d post reached. The post was rejected.',
	1 => 'Weekly posting limit of %d posts reached. This post was rejected.',
];
$a->strings['Users'] = 'Users';
$a->strings['Tools'] = 'Tools';
$a->strings['Contact Blocklist'] = 'Contact block-list';
$a->strings['Server Blocklist'] = 'Server block-list';
$a->strings['Delete Item'] = 'Delete item';
$a->strings['Item Source'] = 'Item source';
$a->strings['Profile Details'] = 'Profile Details';
$a->strings['Only You Can See This'] = 'Only you can see this.';
$a->strings['Tips for New Members'] = 'Tips for New Members';
$a->strings['People Search - %s'] = 'People search - %s';
$a->strings['No matches'] = 'No matches';
$a->strings['Account'] = 'Account';
$a->strings['Two-factor authentication'] = 'Two-factor authentication';
$a->strings['Display'] = 'Display';
$a->strings['Social Networks'] = 'Social networks';
$a->strings['Connected apps'] = 'Connected apps';
$a->strings['Export personal data'] = 'Export personal data';
$a->strings['Remove account'] = 'Remove account';
$a->strings['This page is missing a url parameter.'] = 'This page is missing a URL parameter.';
$a->strings['The post was created'] = 'The post was created';
$a->strings['Failed to remove event'] = 'Failed to remove event';
$a->strings['Event can not end before it has started.'] = 'Event cannot end before it has started.';
$a->strings['Event title and start time are required.'] = 'Event title and starting time are required.';
$a->strings['Starting date and Title are required.'] = 'Starting date and title are required.';
$a->strings['Event Starts:'] = 'Event starts:';
$a->strings['Required'] = 'Required';
$a->strings['Finish date/time is not known or not relevant'] = 'Finish date/time is not known or not relevant';
$a->strings['Event Finishes:'] = 'Event finishes:';
$a->strings['Share this event'] = 'Share this event';
$a->strings['Basic'] = 'Basic';
$a->strings['This calendar format is not supported'] = 'This calendar format is not supported';
$a->strings['No exportable data found'] = 'No exportable data found';
$a->strings['calendar'] = 'calendar';
$a->strings['Events'] = 'Events';
$a->strings['View'] = 'View';
$a->strings['Create New Event'] = 'Create new event';
$a->strings['list'] = 'List';
$a->strings['Contact not found.'] = 'Contact not found.';
$a->strings['Invalid contact.'] = 'Invalid contact.';
$a->strings['Contact is deleted.'] = 'Contact is deleted.';
$a->strings['Bad request.'] = 'Bad request.';
$a->strings['Filter'] = 'Filter';
$a->strings['Members'] = 'Members';
$a->strings['Click on a contact to add or remove.'] = 'Click on a contact to add or remove it.';
$a->strings['%d contact edited.'] = [
	0 => '%d contact edited.',
	1 => '%d contacts edited.',
];
$a->strings['Show all contacts'] = 'Show all contacts';
$a->strings['Pending'] = 'Pending';
$a->strings['Only show pending contacts'] = 'Only show pending contacts.';
$a->strings['Blocked'] = 'Blocked';
$a->strings['Only show blocked contacts'] = 'Only show blocked contacts';
$a->strings['Ignored'] = 'Ignored';
$a->strings['Only show ignored contacts'] = 'Only show ignored contacts';
$a->strings['Archived'] = 'Archived';
$a->strings['Only show archived contacts'] = 'Only show archived contacts';
$a->strings['Hidden'] = 'Hidden';
$a->strings['Only show hidden contacts'] = 'Only show hidden contacts';
$a->strings['Search your contacts'] = 'Search your contacts';
$a->strings['Results for: %s'] = 'Results for: %s';
$a->strings['Update'] = 'Update';
$a->strings['Unblock'] = 'Unblock';
$a->strings['Unignore'] = 'Unignore';
$a->strings['Batch Actions'] = 'Batch actions';
$a->strings['Conversations started by this contact'] = 'Conversations started by this contact';
$a->strings['Posts and Comments'] = 'Posts and Comments';
$a->strings['Advanced Contact Settings'] = 'Advanced contact settings';
$a->strings['Mutual Friendship'] = 'Mutual friendship';
$a->strings['is a fan of yours'] = 'is a fan of yours';
$a->strings['you are a fan of'] = 'I follow them';
$a->strings['Pending outgoing contact request'] = 'Pending outgoing contact request.';
$a->strings['Pending incoming contact request'] = 'Pending incoming contact request.';
$a->strings['Visit %s\'s profile [%s]'] = 'Visit %s\'s profile [%s]';
$a->strings['Contact update failed.'] = 'Contact update failed.';
$a->strings['Return to contact editor'] = 'Return to contact editor';
$a->strings['Name'] = 'Name:';
$a->strings['Account Nickname'] = 'Account nickname:';
$a->strings['Account URL'] = 'Account URL:';
$a->strings['Poll/Feed URL'] = 'Poll/Feed URL:';
$a->strings['New photo from this URL'] = 'New photo from this URL:';
$a->strings['Follower (%s)'] = [
	0 => 'Follower (%s)',
	1 => 'Followers (%s)',
];
$a->strings['Following (%s)'] = [
	0 => 'Following (%s)',
	1 => 'Following (%s)',
];
$a->strings['Mutual friend (%s)'] = [
	0 => 'Mutual friend (%s)',
	1 => 'Mutual friends (%s)',
];
$a->strings['Contact (%s)'] = [
	0 => 'Contact (%s)',
	1 => 'Contacts (%s)',
];
$a->strings['Access denied.'] = 'Access denied.';
$a->strings['Submit Request'] = 'Submit request';
$a->strings['You already added this contact.'] = 'You already added this contact.';
$a->strings['The network type couldn\'t be detected. Contact can\'t be added.'] = 'The network type couldn\'t be detected. Contact can\'t be added.';
$a->strings['Diaspora support isn\'t enabled. Contact can\'t be added.'] = 'diaspora* support isn\'t enabled. Contact can\'t be added.';
$a->strings['OStatus support is disabled. Contact can\'t be added.'] = 'OStatus support is disabled. Contact can\'t be added.';
$a->strings['Please answer the following:'] = 'Please answer the following:';
$a->strings['Your Identity Address:'] = 'My identity address:';
$a->strings['Profile URL'] = 'Profile URL:';
$a->strings['Tags:'] = 'Tags:';
$a->strings['Add a personal note:'] = 'Add a personal note:';
$a->strings['The contact could not be added.'] = 'Contact could not be added.';
$a->strings['Invalid request.'] = 'Invalid request.';
$a->strings['Profile Match'] = 'Profile Match';
$a->strings['Failed to update contact record.'] = 'Failed to update contact record.';
$a->strings['Contact has been unblocked'] = 'Contact has been unblocked';
$a->strings['Contact has been blocked'] = 'Contact has been blocked';
$a->strings['Contact has been unignored'] = 'Contact has been unignored';
$a->strings['Contact has been ignored'] = 'Contact has been ignored';
$a->strings['You are mutual friends with %s'] = 'You are mutual friends with %s';
$a->strings['You are sharing with %s'] = 'You are sharing with %s';
$a->strings['%s is sharing with you'] = '%s is sharing with you';
$a->strings['Private communications are not available for this contact.'] = 'Private communications are not available for this contact.';
$a->strings['Never'] = 'Never';
$a->strings['(Update was not successful)'] = '(Update was not successful)';
$a->strings['(Update was successful)'] = '(Update was successful)';
$a->strings['Suggest friends'] = 'Suggest friends';
$a->strings['Network type: %s'] = 'Network type: %s';
$a->strings['Communications lost with this contact!'] = 'Communications lost with this contact!';
$a->strings['Fetch further information for feeds'] = 'Fetch further information for feeds';
$a->strings['Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'] = 'Fetch information like preview pictures, title, and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.';
$a->strings['Fetch information'] = 'Fetch information';
$a->strings['Fetch keywords'] = 'Fetch keywords';
$a->strings['Fetch information and keywords'] = 'Fetch information and keywords';
$a->strings['No mirroring'] = 'No mirroring';
$a->strings['Mirror as my own posting'] = 'Mirror as my own posting';
$a->strings['Contact Information / Notes'] = 'Personal note';
$a->strings['Contact Settings'] = 'Notification and privacy ';
$a->strings['Contact'] = 'Contact';
$a->strings['Their personal note'] = 'Their personal note';
$a->strings['Edit contact notes'] = 'Edit contact notes';
$a->strings['Block/Unblock contact'] = 'Block/Unblock contact';
$a->strings['Ignore contact'] = 'Ignore contact';
$a->strings['View conversations'] = 'View conversations';
$a->strings['Last update:'] = 'Last update:';
$a->strings['Update public posts'] = 'Update public posts';
$a->strings['Update now'] = 'Update now';
$a->strings['Awaiting connection acknowledge'] = 'Awaiting connection acknowledgement';
$a->strings['Currently blocked'] = 'Currently blocked';
$a->strings['Currently ignored'] = 'Currently ignored';
$a->strings['Currently archived'] = 'Currently archived';
$a->strings['Hide this contact from others'] = 'Hide this contact from others';
$a->strings['Replies/likes to your public posts <strong>may</strong> still be visible'] = 'Replies/Likes to your public posts <strong>may</strong> still be visible';
$a->strings['Notification for new posts'] = 'Notification for new posts';
$a->strings['Send a notification of every new post of this contact'] = 'Send notification for every new post from this contact';
$a->strings['Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected'] = 'Comma-separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected';
$a->strings['Actions'] = 'Actions';
$a->strings['Status'] = 'Status';
$a->strings['Mirror postings from this contact'] = 'Mirror postings from this contact:';
$a->strings['Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'] = 'This will cause Friendica to repost new entries from this contact.';
$a->strings['Refetch contact data'] = 'Re-fetch contact data';
$a->strings['Toggle Blocked status'] = 'Toggle blocked status';
$a->strings['Toggle Ignored status'] = 'Toggle ignored status';
$a->strings['Bad Request.'] = 'Bad request.';
$a->strings['Yes'] = 'Yes';
$a->strings['No suggestions available. If this is a new site, please try again in 24 hours.'] = 'No suggestions available. If this is a new site, please try again in 24 hours.';
$a->strings['You aren\'t following this contact.'] = 'You aren\'t following this contact.';
$a->strings['Unfollowing is currently not supported by your network.'] = 'Unfollowing is currently not supported by your network.';
$a->strings['Disconnect/Unfollow'] = 'Disconnect/Unfollow';
$a->strings['No results.'] = 'No results.';
$a->strings['This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.'] = 'This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.';
$a->strings['Community option not available.'] = 'Community option not available.';
$a->strings['Not available.'] = 'Not available.';
$a->strings['Credits'] = 'Credits';
$a->strings['Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'] = 'Friendica is a community project that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!';
$a->strings['Error'] = [
	0 => 'Error',
	1 => 'Errors',
];
$a->strings['Source input'] = 'Source input';
$a->strings['BBCode::toPlaintext'] = 'BBCode::toPlaintext';
$a->strings['BBCode::convert (raw HTML)'] = 'BBCode::convert (raw HTML)';
$a->strings['BBCode::convert'] = 'BBCode::convert';
$a->strings['BBCode::convert => HTML::toBBCode'] = 'BBCode::convert => HTML::toBBCode';
$a->strings['BBCode::toMarkdown'] = 'BBCode::toMarkdown';
$a->strings['BBCode::toMarkdown => Markdown::convert'] = 'BBCode::toMarkdown => Markdown::convert';
$a->strings['BBCode::toMarkdown => Markdown::toBBCode'] = 'BBCode::toMarkdown => Markdown::toBBCode';
$a->strings['BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode'] = 'BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode';
$a->strings['Item Body'] = 'Item body';
$a->strings['Item Tags'] = 'Item tags';
$a->strings['Source input (Diaspora format)'] = 'Source input (diaspora* format)';
$a->strings['Markdown::convert (raw HTML)'] = 'Markdown::convert (raw HTML)';
$a->strings['Markdown::convert'] = 'Markdown::convert';
$a->strings['Markdown::toBBCode'] = 'Markdown::toBBCode';
$a->strings['Raw HTML input'] = 'Raw HTML input';
$a->strings['HTML Input'] = 'HTML input';
$a->strings['HTML::toBBCode'] = 'HTML::toBBCode';
$a->strings['HTML::toBBCode => BBCode::convert'] = 'HTML::toBBCode => BBCode::convert';
$a->strings['HTML::toBBCode => BBCode::convert (raw HTML)'] = 'HTML::toBBCode => BBCode::convert (raw HTML)';
$a->strings['HTML::toBBCode => BBCode::toPlaintext'] = 'HTML::toBBCode => BBCode::toPlaintext';
$a->strings['HTML::toMarkdown'] = 'HTML::toMarkdown';
$a->strings['HTML::toPlaintext'] = 'HTML::toPlaintext';
$a->strings['HTML::toPlaintext (compact)'] = 'HTML::toPlaintext (compact)';
$a->strings['Source text'] = 'Source text';
$a->strings['BBCode'] = 'BBCode';
$a->strings['Markdown'] = 'Markdown';
$a->strings['HTML'] = 'HTML';
$a->strings['You must be logged in to use this module'] = 'You must be logged in to use this module';
$a->strings['Source URL'] = 'Source URL';
$a->strings['Time Conversion'] = 'Time conversion';
$a->strings['Friendica provides this service for sharing events with other networks and friends in unknown timezones.'] = 'Friendica provides this service for sharing events with other networks and friends in unknown time zones.';
$a->strings['UTC time: %s'] = 'UTC time: %s';
$a->strings['Current timezone: %s'] = 'Current time zone: %s';
$a->strings['Converted localtime: %s'] = 'Converted local time: %s';
$a->strings['Please select your timezone:'] = 'Please select your time zone:';
$a->strings['Only logged in users are permitted to perform a probing.'] = 'Only logged in users are permitted to use the Probe feature.';
$a->strings['Lookup address'] = 'Lookup address';
$a->strings['No entries (some entries may be hidden).'] = 'No entries (entries may be hidden).';
$a->strings['Find on this site'] = 'Find on this site';
$a->strings['Results for:'] = 'Results for:';
$a->strings['Site Directory'] = 'Site directory';
$a->strings['- select -'] = '- select -';
$a->strings['Suggested contact not found.'] = 'Suggested contact not found.';
$a->strings['Friend suggestion sent.'] = 'Friend suggestion sent';
$a->strings['Suggest Friends'] = 'Suggest friends';
$a->strings['Suggest a friend for %s'] = 'Suggest a friend for %s';
$a->strings['Installed addons/apps:'] = 'Installed addons/apps:';
$a->strings['No installed addons/apps'] = 'No installed addons/apps';
$a->strings['Read about the <a href="%1$s/tos">Terms of Service</a> of this node.'] = 'Read about the <a href="%1$s/tos">Terms of Service</a> of this node.';
$a->strings['On this server the following remote servers are blocked.'] = 'On this server the following remote servers are blocked.';
$a->strings['Reason for the block'] = 'Reason for the block';
$a->strings['This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.'] = 'This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.';
$a->strings['Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'] = 'Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.';
$a->strings['Bug reports and issues: please visit'] = 'Bug reports and issues: please visit';
$a->strings['the bugtracker at github'] = 'the bugtracker at github';
$a->strings['Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'] = 'Suggestions, praise, etc. - please email "info" at "friendi - dot - ca';
$a->strings['No profile'] = 'No profile';
$a->strings['Method Not Allowed.'] = 'Method not allowed.';
$a->strings['Help:'] = 'Help:';
$a->strings['Welcome to %s'] = 'Welcome to %s';
$a->strings['Friendica Communications Server - Setup'] = 'Friendica Communications Server - Setup';
$a->strings['System check'] = 'System check';
$a->strings['Next'] = 'Next';
$a->strings['Check again'] = 'Check again';
$a->strings['Base settings'] = 'Base settings';
$a->strings['Base path to installation'] = 'Base path to installation';
$a->strings['If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot.'] = 'If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot.';
$a->strings['Database connection'] = 'Database connection';
$a->strings['In order to install Friendica we need to know how to connect to your database.'] = 'In order to install Friendica we need to know how to connect to your database.';
$a->strings['Please contact your hosting provider or site administrator if you have questions about these settings.'] = 'Please contact your hosting provider or site administrator if you have questions about these settings.';
$a->strings['The database you specify below should already exist. If it does not, please create it before continuing.'] = 'The database you specify below should already exist. If it does not, please create it before continuing.';
$a->strings['Database Server Name'] = 'Database server name';
$a->strings['Database Login Name'] = 'Database login name';
$a->strings['Database Login Password'] = 'Database login password';
$a->strings['For security reasons the password must not be empty'] = 'For security reasons the password must not be empty';
$a->strings['Database Name'] = 'Database name';
$a->strings['Please select a default timezone for your website'] = 'Please select a default time zone for your website';
$a->strings['Site settings'] = 'Site settings';
$a->strings['Site administrator email address'] = 'Site administrator email address';
$a->strings['Your account email address must match this in order to use the web admin panel.'] = 'Your account email address must match this in order to use the web admin panel.';
$a->strings['System Language:'] = 'System language:';
$a->strings['Set the default language for your Friendica installation interface and to send emails.'] = 'Set the default language for your Friendica installation interface and email communication.';
$a->strings['Your Friendica site database has been installed.'] = 'Your Friendica site database has been installed.';
$a->strings['Installation finished'] = 'Installation finished';
$a->strings['<h1>What next</h1>'] = '<h1>What next</h1>';
$a->strings['IMPORTANT: You will need to [manually] setup a scheduled task for the worker.'] = 'IMPORTANT: You will need to [manually] setup a scheduled task for the worker.';
$a->strings['Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.'] = 'Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.';
$a->strings['Total invitation limit exceeded.'] = 'Total invitation limit exceeded';
$a->strings['%s : Not a valid email address.'] = '%s : Not a valid email address';
$a->strings['Please join us on Friendica'] = 'Please join us on Friendica.';
$a->strings['Invitation limit exceeded. Please contact your site administrator.'] = 'Invitation limit is exceeded. Please contact your site administrator.';
$a->strings['%s : Message delivery failed.'] = '%s : Message delivery failed';
$a->strings['%d message sent.'] = [
	0 => '%d message sent.',
	1 => '%d messages sent.',
];
$a->strings['You have no more invitations available'] = 'You have no more invitations available.';
$a->strings['Visit %s for a list of public sites that you can join. Friendica members on other sites can all connect with each other, as well as with members of many other social networks.'] = 'Visit %s for a list of public sites that you can join. Friendica members on other sites can all connect with each other, as well as with members of many other social networks.';
$a->strings['To accept this invitation, please visit and register at %s or any other public Friendica website.'] = 'To accept this invitation, please sign up at %s or any other public Friendica website.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks. See %s for a list of alternate Friendica sites you can join.'] = 'Friendica sites are all inter-connected to create a large privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks. See %s for a list of alternate Friendica sites you can join.';
$a->strings['Our apologies. This system is not currently configured to connect with other public sites or invite members.'] = 'Our apologies. This system is not currently configured to connect with other public sites or invite members.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks.'] = 'Friendica sites are all inter-connected to create a huge privacy-enhanced social web that is owned and controlled by its members. Each site can also connect with many traditional social networks.';
$a->strings['To accept this invitation, please visit and register at %s.'] = 'To accept this invitation, please visit and register at %s.';
$a->strings['Send invitations'] = 'Send invitations';
$a->strings['Enter email addresses, one per line:'] = 'Enter email addresses, one per line:';
$a->strings['You are cordially invited to join me and other close friends on Friendica - and help us to create a better social web.'] = 'You are cordially invited to join me and other close friends on Friendica - and help us to create a better social web.';
$a->strings['You will need to supply this invitation code: $invite_code'] = 'You will need to supply this invitation code: $invite_code';
$a->strings['Once you have registered, please connect with me via my profile page at:'] = 'Once you have signed up, please connect with me via my profile page at:';
$a->strings['For more information about the Friendica project and why we feel it is important, please visit http://friendi.ca'] = 'For more information about the Friendica project and why we feel it is important, please visit http://friendi.ca';
$a->strings['Please enter a post body.'] = 'Please enter a post body.';
$a->strings['This feature is only available with the frio theme.'] = 'This feature is only available with the Frio theme.';
$a->strings['Compose new personal note'] = 'Compose new personal note';
$a->strings['Compose new post'] = 'Compose new post';
$a->strings['Visibility'] = 'Visibility';
$a->strings['Clear the location'] = 'Clear location';
$a->strings['Location services are unavailable on your device'] = 'Location services are unavailable on your device';
$a->strings['Location services are disabled. Please check the website\'s permissions on your device'] = 'Location services are disabled. Please check the website\'s permissions on your device';
$a->strings['The feed for this item is unavailable.'] = 'The feed for this item is unavailable.';
$a->strings['System down for maintenance'] = 'Sorry, the system is currently down for maintenance.';
$a->strings['Files'] = 'Files';
$a->strings['Upload'] = 'Upload';
$a->strings['Sorry, maybe your upload is bigger than the PHP configuration allows'] = 'Sorry, maybe your upload is bigger than the PHP configuration allows';
$a->strings['Or - did you try to upload an empty file?'] = 'Or did you try to upload an empty file?';
$a->strings['File exceeds size limit of %s'] = 'File exceeds size limit of %s';
$a->strings['File upload failed.'] = 'File upload failed.';
$a->strings['Unable to process image.'] = 'Unable to process image.';
$a->strings['Image upload failed.'] = 'Image upload failed.';
$a->strings['Normal Account Page'] = 'Standard';
$a->strings['Soapbox Page'] = 'Soapbox';
$a->strings['Automatic Friend Page'] = 'Love-all';
$a->strings['Personal Page'] = 'Personal Page';
$a->strings['Organisation Page'] = 'Organization Page';
$a->strings['News Page'] = 'News Page';
$a->strings['Relay'] = 'Relay';
$a->strings['%s contact unblocked'] = [
	0 => '%s contact unblocked',
	1 => '%s contacts unblocked',
];
$a->strings['Remote Contact Blocklist'] = 'Remote contact block-list';
$a->strings['This page allows you to prevent any message from a remote contact to reach your node.'] = 'This page allows you to prevent any message from a remote contact to reach your node.';
$a->strings['Block Remote Contact'] = 'Block remote contact';
$a->strings['select all'] = 'select all';
$a->strings['select none'] = 'select none';
$a->strings['No remote contact is blocked from this node.'] = 'No remote contact is blocked from this node.';
$a->strings['Blocked Remote Contacts'] = 'Blocked remote contacts';
$a->strings['Block New Remote Contact'] = 'Block new remote contact';
$a->strings['Photo'] = 'Photo';
$a->strings['Reason'] = 'Reason';
$a->strings['%s total blocked contact'] = [
	0 => '%s total blocked contact',
	1 => '%s blocked contacts',
];
$a->strings['URL of the remote contact to block.'] = 'URL of the remote contact to block.';
$a->strings['Block Reason'] = 'Block reason';
$a->strings['Server Domain Pattern'] = 'Server Domain Pattern';
$a->strings['Block reason'] = 'Block reason';
$a->strings['Blocked server domain pattern'] = 'Blocked server domain pattern';
$a->strings['Delete server domain pattern'] = 'Delete server domain pattern';
$a->strings['Check to delete this entry from the blocklist'] = 'Check to delete this entry from the block-list';
$a->strings['Server Domain Pattern Blocklist'] = 'Server domain pattern block-list';
$a->strings['The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'] = 'The list of blocked server domain patterns will be made publicly available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.';
$a->strings['Save changes to the blocklist'] = 'Save changes to the block-list';
$a->strings['Current Entries in the Blocklist'] = 'Current entries in the block-list';
$a->strings['Item marked for deletion.'] = 'Item marked for deletion.';
$a->strings['Delete this Item'] = 'Delete';
$a->strings['On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'] = 'Here you can delete an item from this node. If the item is a top-level posting, the entire thread will be deleted.';
$a->strings['You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'] = 'You need to know the global unique identifier (GUID) of the item, which you can find by looking at the display URL. The last part of http://example.com/display/123456 is the GUID: i.e. 123456.';
$a->strings['GUID'] = 'GUID';
$a->strings['The GUID of the item you want to delete.'] = 'GUID of item to be deleted.';
$a->strings['Type'] = 'Type';
$a->strings['Item not found'] = 'Item not found';
$a->strings['Item Guid'] = 'Item Guid';
$a->strings['Normal Account'] = 'Standard account';
$a->strings['Automatic Follower Account'] = 'Automatic follower account';
$a->strings['Automatic Friend Account'] = 'Automatic friend account';
$a->strings['Blog Account'] = 'Blog account';
$a->strings['Registered users'] = 'Signed up users';
$a->strings['Pending registrations'] = 'Pending registrations';
$a->strings['%s user blocked'] = [
	0 => '%s user blocked',
	1 => '%s users blocked',
];
$a->strings['You can\'t remove yourself'] = 'You can\'t remove yourself';
$a->strings['%s user deleted'] = [
	0 => '%s user deleted',
	1 => '%s users deleted',
];
$a->strings['User "%s" deleted'] = 'User "%s" deleted';
$a->strings['User "%s" blocked'] = 'User "%s" blocked';
$a->strings['Register date'] = 'Registration date';
$a->strings['Last login'] = 'Last login';
$a->strings['User blocked'] = 'User blocked';
$a->strings['Site admin'] = 'Site admin';
$a->strings['Account expired'] = 'Account expired';
$a->strings['Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Selected users will be deleted!\n\nEverything these users have posted on this site will be permanently deleted!\n\nAre you sure?';
$a->strings['The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'] = 'The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?';
$a->strings['%s user unblocked'] = [
	0 => '%s user unblocked',
	1 => '%s users unblocked',
];
$a->strings['User "%s" unblocked'] = 'User "%s" unblocked';
$a->strings['New User'] = 'New user';
$a->strings['Add User'] = 'Add user';
$a->strings['Name of the new user.'] = 'Name of the new user.';
$a->strings['Nickname'] = 'Nickname';
$a->strings['Nickname of the new user.'] = 'Nickname of the new user.';
$a->strings['Email address of the new user.'] = 'Email address of the new user.';
$a->strings['Permanent deletion'] = 'Permanent deletion';
$a->strings['User waiting for permanent deletion'] = 'User awaiting permanent deletion';
$a->strings['Account approved.'] = 'Account approved.';
$a->strings['Request date'] = 'Request date';
$a->strings['No registrations.'] = 'No registrations.';
$a->strings['Note from the user'] = 'Note from the user';
$a->strings['Deny'] = 'Deny';
$a->strings['Show Ignored Requests'] = 'Show ignored requests.';
$a->strings['Hide Ignored Requests'] = 'Hide ignored requests';
$a->strings['Notification type:'] = 'Notification type:';
$a->strings['Suggested by:'] = 'Suggested by:';
$a->strings['Claims to be known to you: '] = 'Says they know me:';
$a->strings['No'] = 'No';
$a->strings['Shall your connection be bidirectional or not?'] = 'Shall your connection be in both directions or not?';
$a->strings['Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.'] = 'Accepting %s as a friend allows %s to subscribe to your posts. You will also receive updates from them in your news feed.';
$a->strings['Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.'] = 'Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.';
$a->strings['Friend'] = 'Friend';
$a->strings['Subscriber'] = 'Subscriber';
$a->strings['No introductions.'] = 'No introductions.';
$a->strings['No more %s notifications.'] = 'No more %s notifications.';
$a->strings['Network Notifications'] = 'Network notifications';
$a->strings['System Notifications'] = 'System notifications';
$a->strings['Personal Notifications'] = 'Personal notifications';
$a->strings['Home Notifications'] = 'Home notifications';
$a->strings['Show unread'] = 'Show unread';
$a->strings['{0} requested registration'] = '{0} requested registration';
$a->strings['Authorize application connection'] = 'Authorize application connection';
$a->strings['Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'] = 'Do you want to authorize this application to access your posts and contacts and create new posts for you?';
$a->strings['Resubscribing to OStatus contacts'] = 'Resubscribing to OStatus contacts';
$a->strings['Keep this window open until done.'] = 'Keep this window open until done.';
$a->strings['No contact provided.'] = 'No contact provided.';
$a->strings['Couldn\'t fetch information for contact.'] = 'Couldn\'t fetch information for contact.';
$a->strings['Couldn\'t fetch friends for contact.'] = 'Couldn\'t fetch friends for contact.';
$a->strings['Done'] = 'Done';
$a->strings['success'] = 'success';
$a->strings['failed'] = 'failed';
$a->strings['ignored'] = 'Ignored';
$a->strings['Remote privacy information not available.'] = 'Remote privacy information not available.';
$a->strings['Visible to:'] = 'Visible to:';
$a->strings['Invalid photo with id %s.'] = 'Invalid photo with id %s.';
$a->strings['Edit post'] = 'Edit post';
$a->strings['web link'] = 'web link';
$a->strings['Insert video link'] = 'Insert video link';
$a->strings['video link'] = 'video link';
$a->strings['Insert audio link'] = 'Insert audio link';
$a->strings['audio link'] = 'audio link';
$a->strings['Remove Item Tag'] = 'Remove Item tag';
$a->strings['Select a tag to remove: '] = 'Select a tag to remove: ';
$a->strings['Remove'] = 'Remove';
$a->strings['No contacts.'] = 'No contacts.';
$a->strings['%s\'s timeline'] = '%s\'s timeline';
$a->strings['%s\'s posts'] = '%s\'s posts';
$a->strings['%s\'s comments'] = '%s\'s comments';
$a->strings['Image exceeds size limit of %s'] = 'Image exceeds size limit of %s';
$a->strings['Image upload didn\'t complete, please try again'] = 'Image upload didn\'t complete. Please try again.';
$a->strings['Image file is missing'] = 'Image file is missing';
$a->strings['Server can\'t accept new file upload at this time, please contact your administrator'] = 'Server can\'t accept new file uploads at this time. Please contact your administrator.';
$a->strings['Image file is empty.'] = 'Image file is empty.';
$a->strings['View Album'] = 'View album';
$a->strings['Profile not found.'] = 'Profile not found.';
$a->strings['Full Name:'] = 'Full name:';
$a->strings['Member since:'] = 'Member since:';
$a->strings['j F, Y'] = 'j F, Y';
$a->strings['j F'] = 'j F';
$a->strings['Birthday:'] = 'Birthday:';
$a->strings['Age: '] = 'Age: ';
$a->strings['Description:'] = 'Description:';
$a->strings['Profile unavailable.'] = 'Profile unavailable.';
$a->strings['Invalid locator'] = 'Invalid locator';
$a->strings['Remote subscription can\'t be done for your network. Please subscribe directly on your system.'] = 'Remote subscription can\'t be done for your network. Please subscribe directly on your system.';
$a->strings['Friend/Connection Request'] = 'Friend/Connection request';
$a->strings['Enter your Webfinger address (user@domain.tld) or profile URL here. If this isn\'t supported by your system, you have to subscribe to <strong>%s</strong> or <strong>%s</strong> directly on your system.'] = 'Enter your WebFinger address (user@domain.tld) or profile URL here. If this isn\'t supported by your system, you have to subscribe to <strong>%s</strong> or <strong>%s</strong> directly on your system.';
$a->strings['Your Webfinger address or profile URL:'] = 'Your WebFinger address or profile URL:';
$a->strings['Unable to check your home location.'] = 'Unable to check your home location.';
$a->strings['Number of daily wall messages for %s exceeded. Message failed.'] = 'Number of daily wall messages for %s exceeded. Message failed.';
$a->strings['If you wish for %s to respond, please check that the privacy settings on your site allow private mail from unknown senders.'] = 'If you wish for %s to respond, please check that the privacy settings on your site allow private mail from unknown senders.';
$a->strings['This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'] = 'This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.';
$a->strings['You may (optionally) fill in this form via OpenID by supplying your OpenID and clicking "Register".'] = 'You may (optionally) fill in this form via OpenID by supplying your OpenID and clicking "Register".';
$a->strings['If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.'] = 'If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.';
$a->strings['Your OpenID (optional): '] = 'Your OpenID (optional): ';
$a->strings['Include your profile in member directory?'] = 'Include your profile in member directory?';
$a->strings['Note for the admin'] = 'Note for the admin';
$a->strings['Leave a message for the admin, why you want to join this node'] = 'Leave a message for the admin. Why do you want to join this node?';
$a->strings['Membership on this site is by invitation only.'] = 'Membership on this site is by invitation only.';
$a->strings['Your invitation code: '] = 'Your invitation code: ';
$a->strings['Your Full Name (e.g. Joe Smith, real or real-looking): '] = 'Your full name: ';
$a->strings['Your Email Address: (Initial information will be send there, so this has to be an existing address.)'] = 'Your Email Address: (Initial information will be sent there, so this must be an existing address.)';
$a->strings['Please repeat your e-mail address:'] = 'Please repeat your email address:';
$a->strings['New Password:'] = 'New password:';
$a->strings['Leave empty for an auto generated password.'] = 'Leave empty for an auto generated password.';
$a->strings['Confirm:'] = 'Confirm new password:';
$a->strings['Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be "<strong>nickname@%s</strong>".'] = 'Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be "<strong>nickname@%s</strong>".';
$a->strings['Choose a nickname: '] = 'Choose a nickname: ';
$a->strings['Import'] = 'Import profile';
$a->strings['Import your profile to this friendica instance'] = 'Import an existing Friendica profile to this node.';
$a->strings['Note: This node explicitly contains adult content'] = 'Note: This node explicitly contains adult content';
$a->strings['Parent Password:'] = 'Parent Password:';
$a->strings['Please enter the password of the parent account to legitimize your request.'] = 'Please enter the password of the parent account to authorize this request.';
$a->strings['You have entered too much information.'] = 'You have entered too much information.';
$a->strings['Registration successful. Please check your email for further instructions.'] = 'Registration successful. Please check your email for further instructions.';
$a->strings['Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.'] = 'Failed to send email message. Here are your account details:<br> login: %s<br> password: %s<br><br>You can change your password after login.';
$a->strings['Registration successful.'] = 'Registration successful.';
$a->strings['Your registration can not be processed.'] = 'Your registration cannot be processed.';
$a->strings['You have to leave a request note for the admin.'] = 'You have to leave a request note for the admin.';
$a->strings['An internal error occured.'] = 'An internal error occurred.';
$a->strings['Your registration is pending approval by the site owner.'] = 'Your registration is pending approval by the site administrator.';
$a->strings['You must be logged in to use this module.'] = 'You must be logged in to use this module.';
$a->strings['Only logged in users are permitted to perform a search.'] = 'Only logged in users are permitted to perform a search.';
$a->strings['Only one search per minute is permitted for not logged in users.'] = 'Only one search per minute is permitted for not-logged-in users.';
$a->strings['Items tagged with: %s'] = 'Items tagged with: %s';
$a->strings['Search term already saved.'] = 'Search term already saved.';
$a->strings['Create a New Account'] = 'Create a new account';
$a->strings['Your OpenID: '] = 'Your OpenID: ';
$a->strings['Please enter your username and password to add the OpenID to your existing account.'] = 'Please enter your username and password to add the OpenID to your existing account.';
$a->strings['Or login using OpenID: '] = 'Or login with OpenID: ';
$a->strings['Password: '] = 'Password: ';
$a->strings['Remember me'] = 'Remember me';
$a->strings['Forgot your password?'] = 'Forgot your password?';
$a->strings['Website Terms of Service'] = 'Website Terms of Service';
$a->strings['terms of service'] = 'Terms of service';
$a->strings['Website Privacy Policy'] = 'Website Privacy Policy';
$a->strings['privacy policy'] = 'Privacy policy';
$a->strings['Logged out.'] = 'Logged out.';
$a->strings['Account not found. Please login to your existing account to add the OpenID to it.'] = 'Account not found. Please login to your existing account to add the OpenID to it.';
$a->strings['Account not found. Please register a new account or login to your existing account to add the OpenID to it.'] = 'Account not found. Please register a new account or login to your existing account to add the OpenID.';
$a->strings['Passwords do not match.'] = 'Passwords do not match.';
$a->strings['Password unchanged.'] = 'Password unchanged.';
$a->strings['Current Password:'] = 'Current password:';
$a->strings['Your current password to confirm the changes'] = 'Current password to confirm change';
$a->strings['Remaining recovery codes: %d'] = 'Remaining recovery codes: %d';
$a->strings['Invalid code, please retry.'] = 'Invalid code, please try again.';
$a->strings['Two-factor recovery'] = 'Two-factor recovery';
$a->strings['<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'] = '<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>';
$a->strings['Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>'] = 'Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>';
$a->strings['Please enter a recovery code'] = 'Please enter a recovery code';
$a->strings['Submit recovery code and complete login'] = 'Submit recovery code and complete login';
$a->strings['<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'] = '<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>';
$a->strings['Please enter a code from your authentication app'] = 'Please enter a code from your authentication app';
$a->strings['Verify code and complete login'] = 'Verify code and complete login';
$a->strings['Wrong Password.'] = 'Wrong password.';
$a->strings['Invalid email.'] = 'Invalid email.';
$a->strings['Cannot change to that email.'] = 'Cannot change to that email.';
$a->strings['Contact CSV file upload error'] = 'Contact CSV file upload error';
$a->strings['Importing Contacts done'] = 'Importing contacts done';
$a->strings['Relocate message has been send to your contacts'] = 'Relocate message has been sent to your contacts';
$a->strings['Unable to find your profile. Please contact your admin.'] = 'Unable to find your profile. Please contact your admin.';
$a->strings['Personal Page Subtypes'] = 'Personal Page subtypes';
$a->strings['Account for a personal profile.'] = 'Account for a personal profile.';
$a->strings['Account for an organisation that automatically approves contact requests as "Followers".'] = 'Account for an organization that automatically approves contact requests as "Followers".';
$a->strings['Account for a news reflector that automatically approves contact requests as "Followers".'] = 'Account for a news reflector that automatically approves contact requests as "Followers".';
$a->strings['Account for community discussions.'] = 'Account for community discussions.';
$a->strings['Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'] = 'Account for a regular personal profile that requires manual approval of "Friends" and "Followers".';
$a->strings['Account for a public profile that automatically approves contact requests as "Followers".'] = 'Account for a public profile that automatically approves contact requests as "Followers".';
$a->strings['Automatically approves all contact requests.'] = 'Automatically approves all contact requests.';
$a->strings['Account for a popular profile that automatically approves contact requests as "Friends".'] = 'Account for a popular profile that automatically approves contact requests as "Friends".';
$a->strings['Requires manual approval of contact requests.'] = 'Requires manual approval of contact requests.';
$a->strings['OpenID:'] = 'OpenID:';
$a->strings['(Optional) Allow this OpenID to login to this account.'] = '(Optional) Allow this OpenID to login to this account.';
$a->strings['Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.'] = 'Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.';
$a->strings['Account Settings'] = 'Account Settings';
$a->strings['Your Identity Address is <strong>\'%s\'</strong> or \'%s\'.'] = 'My identity address: <strong>\'%s\'</strong> or \'%s\'';
$a->strings['Password Settings'] = 'Password change';
$a->strings['Leave password fields blank unless changing'] = 'Leave password fields blank unless changing';
$a->strings['Password:'] = 'Password:';
$a->strings['Your current password to confirm the changes of the email address'] = 'Your current password to confirm the change of your email address.';
$a->strings['Delete OpenID URL'] = 'Delete OpenID URL';
$a->strings['Basic Settings'] = 'Basic information';
$a->strings['Email Address:'] = 'Email address:';
$a->strings['Your Timezone:'] = 'Time zone:';
$a->strings['Your Language:'] = 'Language:';
$a->strings['Set the language we use to show you friendica interface and to send you emails'] = 'Set the language of your Friendica interface and emails sent to you.';
$a->strings['Default Post Location:'] = 'Posting location:';
$a->strings['Use Browser Location:'] = 'Use browser location:';
$a->strings['Security and Privacy Settings'] = 'Security and privacy';
$a->strings['Maximum Friend Requests/Day:'] = 'Maximum friend requests per day:';
$a->strings['(to prevent spam abuse)'] = 'May prevent spam and abusive registrations';
$a->strings['Allow friends to post to your profile page?'] = 'Allow friends to post to my wall?';
$a->strings['Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'] = 'Your contacts may write posts on your profile wall. These posts will be distributed to your contacts';
$a->strings['Allow friends to tag your posts?'] = 'Allow friends to tag my post?';
$a->strings['Your contacts can add additional tags to your posts.'] = 'Your contacts can add additional tags to your posts.';
$a->strings['Permit unknown people to send you private mail?'] = 'Allow unknown people to send me private messages?';
$a->strings['Friendica network users may send you private messages even if they are not in your contact list.'] = 'Friendica network users may send you private messages even if they are not in your contact list.';
$a->strings['Maximum private messages per day from unknown people:'] = 'Maximum private messages per day from unknown people:';
$a->strings['Default Post Permissions'] = 'Default post permissions';
$a->strings['Automatically expire posts after this many days:'] = 'Automatically expire posts after this many days:';
$a->strings['If empty, posts will not expire. Expired posts will be deleted'] = 'Posts will not expire if empty;  expired posts will be deleted';
$a->strings['When activated, posts and comments will be expired.'] = 'If activated, posts and comments will expire.';
$a->strings['Expire personal notes'] = 'Expire personal notes';
$a->strings['When activated, the personal notes on your profile page will be expired.'] = 'If activated, the personal notes on your profile page will expire.';
$a->strings['When activated, your own posts never expire. Then the settings above are only valid for posts you received.'] = 'If activated, your own posts never expire. The settings above are only valid for posts you received.';
$a->strings['Notification Settings'] = 'Notification';
$a->strings['Send a notification email when:'] = 'Send notification email when:';
$a->strings['You receive an introduction'] = 'Receiving an introduction';
$a->strings['Your introductions are confirmed'] = 'My introductions are confirmed';
$a->strings['Someone writes on your profile wall'] = 'Someone writes on my wall';
$a->strings['Someone writes a followup comment'] = 'A follow up comment is posted';
$a->strings['You receive a private message'] = 'receiving a private message';
$a->strings['You receive a friend suggestion'] = 'Receiving a friend suggestion';
$a->strings['You are tagged in a post'] = 'Tagged in a post';
$a->strings['Activate desktop notifications'] = 'Activate desktop notifications';
$a->strings['Show desktop popup on new notifications'] = 'Show desktop pop-up on new notifications';
$a->strings['Text-only notification emails'] = 'Text-only notification emails';
$a->strings['Send text only notification emails, without the html part'] = 'Receive text only emails without HTML ';
$a->strings['Show detailled notifications'] = 'Show detailed notifications';
$a->strings['Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.'] = 'By default, notifications are condensed into a single notification for each item. If enabled, every notification is displayed.';
$a->strings['Advanced Account/Page Type Settings'] = 'Advanced account types';
$a->strings['Change the behaviour of this account for special situations'] = 'Change behavior of this account for special situations';
$a->strings['Import Contacts'] = 'Import contacts';
$a->strings['Upload a CSV file that contains the handle of your followed accounts in the first column you exported from the old account.'] = 'Upload a CSV file that contains the handle of your followed accounts in the first column you exported from the old account.';
$a->strings['Upload File'] = 'Upload file';
$a->strings['Relocate'] = 'Recent relocation';
$a->strings['If you have moved this profile from another server, and some of your contacts don\'t receive your updates, try pushing this button.'] = 'If you have moved this profile from another server and some of your contacts don\'t receive your updates:';
$a->strings['Resend relocate message to contacts'] = 'Resend relocation message to contacts';
$a->strings['Addon Settings'] = 'Addon Settings';
$a->strings['No Addon settings configured'] = 'No addon settings configured';
$a->strings['Description'] = 'Description';
$a->strings['Add'] = 'Add';
$a->strings['Failed to connect with email account using the settings provided.'] = 'Failed to connect with email account using the settings provided.';
$a->strings['Diaspora (Socialhome, Hubzilla)'] = 'diaspora* (Socialhome, Hubzilla)';
$a->strings['Email access is disabled on this site.'] = 'Email access is disabled on this site.';
$a->strings['None'] = 'None';
$a->strings['General Social Media Settings'] = 'General Social Media Settings';
$a->strings['Attach the link title'] = 'Attach the link title';
$a->strings['When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.'] = 'If activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.';
$a->strings['Repair OStatus subscriptions'] = 'Repair OStatus subscriptions';
$a->strings['Email/Mailbox Setup'] = 'Email/Mailbox setup';
$a->strings['If you wish to communicate with email contacts using this service (optional), please specify how to connect to your mailbox.'] = 'Specify how to connect to your mailbox, if you wish to communicate with existing email contacts.';
$a->strings['Last successful email check:'] = 'Last successful email check:';
$a->strings['IMAP server name:'] = 'IMAP server name:';
$a->strings['IMAP port:'] = 'IMAP port:';
$a->strings['Security:'] = 'Security:';
$a->strings['Email login name:'] = 'Email login name:';
$a->strings['Email password:'] = 'Email password:';
$a->strings['Reply-to address:'] = 'Reply-to address:';
$a->strings['Send public posts to all email contacts:'] = 'Send public posts to all email contacts:';
$a->strings['Action after import:'] = 'Action after import:';
$a->strings['Move to folder'] = 'Move to folder';
$a->strings['Move to folder:'] = 'Move to folder:';
$a->strings['Delegation successfully granted.'] = 'Delegation successfully granted.';
$a->strings['Parent user not found, unavailable or password doesn\'t match.'] = 'Parent user not found, unavailable or password doesn\'t match.';
$a->strings['Delegation successfully revoked.'] = 'Delegation successfully revoked.';
$a->strings['Delegated administrators can view but not change delegation permissions.'] = 'Delegated administrators can view but not change delegation permissions.';
$a->strings['Delegate user not found.'] = 'Delegate user not found.';
$a->strings['No parent user'] = 'No parent user';
$a->strings['Parent User'] = 'Parent user';
$a->strings['Parent users have total control about this account, including the account settings. Please double check whom you give this access.'] = 'Parent users have total control of this account, including core settings. Please double-check whom you grant such access.';
$a->strings['Delegates'] = 'Delegates';
$a->strings['Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'] = 'Delegates are able to manage all aspects of this account except for key setting features. Please do not delegate your personal account to anybody that you do not trust completely.';
$a->strings['Existing Page Delegates'] = 'Existing page delegates';
$a->strings['Potential Delegates'] = 'Potential delegates';
$a->strings['No entries.'] = 'No entries.';
$a->strings['The theme you chose isn\'t available.'] = 'The theme you chose isn\'t available.';
$a->strings['%s - (Unsupported)'] = '%s - (Unsupported)';
$a->strings['Display Settings'] = 'Display Settings';
$a->strings['General Theme Settings'] = 'Themes';
$a->strings['Custom Theme Settings'] = 'Theme customization';
$a->strings['Content Settings'] = 'Content/Layout';
$a->strings['Theme settings'] = 'Theme settings';
$a->strings['Display Theme:'] = 'Display theme:';
$a->strings['Mobile Theme:'] = 'Mobile theme:';
$a->strings['Number of items to display per page:'] = 'Number of items displayed per page:';
$a->strings['Maximum of 100 items'] = 'Maximum of 100 items';
$a->strings['Number of items to display per page when viewed from mobile device:'] = 'Number of items displayed per page on mobile devices:';
$a->strings['Update browser every xx seconds'] = 'Update browser every so many seconds:';
$a->strings['Minimum of 10 seconds. Enter -1 to disable it.'] = 'Minimum 10 seconds; to disable -1.';
$a->strings['Infinite scroll'] = 'Infinite scroll';
$a->strings['Beginning of week:'] = 'Week begins: ';
$a->strings['Additional Features'] = 'Additional Features';
$a->strings['Connected Apps'] = 'Connected Apps';
$a->strings['Remove authorization'] = 'Remove authorization';
$a->strings['Field Permissions'] = 'Field Permissions';
$a->strings['(click to open/close)'] = '(reveal/hide)';
$a->strings['Profile Actions'] = 'Profile actions';
$a->strings['Edit Profile Details'] = 'Edit Profile Details';
$a->strings['Change Profile Photo'] = 'Change profile photo';
$a->strings['Profile picture'] = 'Profile picture';
$a->strings['Location'] = 'Location';
$a->strings['Miscellaneous'] = 'Miscellaneous';
$a->strings['Upload Profile Photo'] = 'Upload profile photo';
$a->strings['Street Address:'] = 'Street address:';
$a->strings['Locality/City:'] = 'Locality/City:';
$a->strings['Region/State:'] = 'Region/State:';
$a->strings['Postal/Zip Code:'] = 'Postcode:';
$a->strings['Country:'] = 'Country:';
$a->strings['XMPP (Jabber) address:'] = 'XMPP (Jabber) address:';
$a->strings['Homepage URL:'] = 'Homepage URL:';
$a->strings['Public Keywords:'] = 'Public keywords:';
$a->strings['(Used for suggesting potential friends, can be seen by others)'] = 'Used for suggesting potential friends, can be seen by others.';
$a->strings['Private Keywords:'] = 'Private keywords:';
$a->strings['(Used for searching profiles, never shown to others)'] = 'Used for searching profiles, never shown to others.';
$a->strings['Image size reduction [%s] failed.'] = 'Image size reduction [%s] failed.';
$a->strings['Shift-reload the page or clear browser cache if the new photo does not display immediately.'] = 'Shift-reload the page or clear browser cache if the new photo does not display immediately.';
$a->strings['Unable to process image'] = 'Unable to process image';
$a->strings['Crop Image'] = 'Crop Image';
$a->strings['Please adjust the image cropping for optimum viewing.'] = 'Please adjust the image cropping for optimum viewing.';
$a->strings['or'] = 'or';
$a->strings['skip this step'] = 'skip this step';
$a->strings['select a photo from your photo albums'] = 'select a photo from your photo albums';
$a->strings['[Friendica System Notify]'] = '[Friendica System Notify]';
$a->strings['User deleted their account'] = 'User deleted their account';
$a->strings['On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'] = 'A user deleted his or her account on your Friendica node. Please ensure these data are removed from the backups.';
$a->strings['The user id is %d'] = 'The user id is %d';
$a->strings['Remove My Account'] = 'Remove My Account';
$a->strings['This will completely remove your account. Once this has been done it is not recoverable.'] = 'This will completely remove your account. Once this has been done it is not recoverable.';
$a->strings['Please enter your password for verification:'] = 'Please enter your password for verification:';
$a->strings['Please enter your password to access this page.'] = 'Please enter your password to access this page.';
$a->strings['App-specific password generation failed: The description is empty.'] = 'App-specific password generation failed: The description is empty.';
$a->strings['App-specific password generation failed: This description already exists.'] = 'App-specific password generation failed: This description already exists.';
$a->strings['New app-specific password generated.'] = 'New app-specific password generated.';
$a->strings['App-specific passwords successfully revoked.'] = 'App-specific passwords successfully revoked.';
$a->strings['App-specific password successfully revoked.'] = 'App-specific password successfully revoked.';
$a->strings['Two-factor app-specific passwords'] = 'Two-factor app-specific passwords';
$a->strings['<p>App-specific passwords are randomly generated passwords used instead your regular password to authenticate your account on third-party applications that don\'t support two-factor authentication.</p>'] = '<p>App-specific passwords are randomly generated passwords. They are used instead of your regular password to authenticate your account on third-party applications that don\'t support two-factor authentication.</p>';
$a->strings['Make sure to copy your new app-specific password now. You won’t be able to see it again!'] = 'Make sure to copy your new app-specific password now. You won’t be able to see it again!';
$a->strings['Last Used'] = 'Last used';
$a->strings['Revoke'] = 'Revoke';
$a->strings['Revoke All'] = 'Revoke all';
$a->strings['When you generate a new app-specific password, you must use it right away, it will be shown to you once after you generate it.'] = 'When you generate a new app-specific password, you must use it right away. It will be shown to you only once after you generate it.';
$a->strings['Generate new app-specific password'] = 'Generate new app-specific password';
$a->strings['Friendiqa on my Fairphone 2...'] = 'Friendiqa on my Fairphone 2...';
$a->strings['Generate'] = 'Generate';
$a->strings['Two-factor authentication successfully disabled.'] = 'Two-factor authentication successfully disabled.';
$a->strings['<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>'] = '<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>';
$a->strings['Authenticator app'] = 'Authenticator app';
$a->strings['Configured'] = 'Configured';
$a->strings['Not Configured'] = 'Not configured';
$a->strings['<p>You haven\'t finished configuring your authenticator app.</p>'] = '<p>You haven\'t finished configuring your authenticator app.</p>';
$a->strings['<p>Your authenticator app is correctly configured.</p>'] = '<p>Your authenticator app is correctly configured.</p>';
$a->strings['Recovery codes'] = 'Recovery codes';
$a->strings['Remaining valid codes'] = 'Remaining valid codes';
$a->strings['<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>'] = '<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>';
$a->strings['App-specific passwords'] = 'App-specific passwords';
$a->strings['Generated app-specific passwords'] = 'Generated app-specific passwords.';
$a->strings['<p>These randomly generated passwords allow you to authenticate on apps not supporting two-factor authentication.</p>'] = '<p>These randomly generated passwords allow you to authenticate on apps not supporting two-factor authentication.</p>';
$a->strings['Current password:'] = 'Current password:';
$a->strings['You need to provide your current password to change two-factor authentication settings.'] = 'You need to provide your current password to change two-factor authentication settings.';
$a->strings['Enable two-factor authentication'] = 'Enable two-factor authentication';
$a->strings['Disable two-factor authentication'] = 'Disable two-factor authentication';
$a->strings['Show recovery codes'] = 'Show recovery codes';
$a->strings['Manage app-specific passwords'] = 'Manage app-specific passwords.';
$a->strings['Finish app configuration'] = 'Finish app configuration';
$a->strings['New recovery codes successfully generated.'] = 'New recovery codes successfully generated.';
$a->strings['Two-factor recovery codes'] = 'Two-factor recovery codes';
$a->strings['<p>Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.</p><p><strong>Put these in a safe spot!</strong> If you lose your device and don’t have the recovery codes you will lose access to your account.</p>'] = '<p>Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.</p><p><strong>Put these in a safe place!</strong> If you lose your device and don’t have the recovery codes you will lose access to your account.</p>';
$a->strings['When you generate new recovery codes, you must copy the new codes. Your old codes won’t work anymore.'] = 'When you generate new recovery codes, you must copy the new codes. Your old codes won’t work anymore.';
$a->strings['Generate new recovery codes'] = 'Generate new recovery codes';
$a->strings['Next: Verification'] = 'Next: Verification';
$a->strings['Two-factor authentication successfully activated.'] = 'Two-factor authentication successfully activated.';
$a->strings['<p>Or you can submit the authentication settings manually:</p>
<dl>
	<dt>Issuer</dt>
	<dd>%s</dd>
	<dt>Account Name</dt>
	<dd>%s</dd>
	<dt>Secret Key</dt>
	<dd>%s</dd>
	<dt>Type</dt>
	<dd>Time-based</dd>
	<dt>Number of digits</dt>
	<dd>6</dd>
	<dt>Hashing algorithm</dt>
	<dd>SHA-1</dd>
</dl>'] = '<p>Or you can submit the authentication settings manually:</p>
<dl>
	<dt>Issuer</dt>
	<dd>%s</dd>
	<dt>Account Name</dt>
	<dd>%s</dd>
	<dt>Secret Key</dt>
	<dd>%s</dd>
	<dt>Type</dt>
	<dd>Time-based</dd>
	<dt>Number of digits</dt>
	<dd>6</dd>
	<dt>Hashing algorithm</dt>
	<dd>SHA-1</dd>
</dl>';
$a->strings['Two-factor code verification'] = 'Two-factor code verification';
$a->strings['<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>'] = '<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>';
$a->strings['Verify code and enable two-factor authentication'] = 'Verify code and enable two-factor authentication';
$a->strings['Export account'] = 'Export account';
$a->strings['Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.'] = 'Export your account info and contacts. Use this to backup your account or to move it to another server.';
$a->strings['Export all'] = 'Export all';
$a->strings['Export Contacts to CSV'] = 'Export contacts to CSV';
$a->strings['Export the list of the accounts you are following as CSV file. Compatible to e.g. Mastodon.'] = 'Export the list of the accounts you are following as CSV file. Compatible with Mastodon for example.';
$a->strings['At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'] = 'At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), a username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but won’t be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.';
$a->strings['This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.'] = 'This information is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional personal information that may be transmitted to the communication partner\'s accounts.';
$a->strings['Privacy Statement'] = 'Privacy Statement';
$a->strings['The requested item doesn\'t exist or has been deleted.'] = 'The requested item doesn\'t exist or has been deleted.';
$a->strings['Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'] = 'Accounts that I manage or own.';
$a->strings['Select an identity to manage: '] = 'Select identity:';
$a->strings['User imports on closed servers can only be done by an administrator.'] = 'User imports on closed servers can only be done by an administrator.';
$a->strings['Move account'] = 'Move Existing Friendica Account';
$a->strings['You can import an account from another Friendica server.'] = 'You can import an existing Friendica profile to this node.';
$a->strings['You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.'] = 'You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.';
$a->strings['This feature is experimental. We can\'t import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora'] = 'This feature is experimental. We can\'t import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora.';
$a->strings['Account file'] = 'Account file:';
$a->strings['To export your account, go to "Settings->Export your personal data" and select "Export account"'] = 'To export your account, go to "Settings->Export personal data" and select "Export account"';
$a->strings['Error decoding account file'] = 'Error decoding account file';
$a->strings['Error! No version data in file! This is not a Friendica account file?'] = 'Error! No version data in file! Is this a Friendica account file?';
$a->strings['User \'%s\' already exists on this server!'] = 'User \'%s\' already exists on this server!';
$a->strings['User creation error'] = 'User creation error';
$a->strings['%d contact not imported'] = [
	0 => '%d contact not imported',
	1 => '%d contacts not imported',
];
$a->strings['User profile creation error'] = 'User profile creation error';
$a->strings['Done. You can now login with your username and password'] = 'Done. You can now login with your username and password';
$a->strings['Welcome to Friendica'] = 'Welcome to Friendica';
$a->strings['New Member Checklist'] = 'New Member Checklist';
$a->strings['We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.'] = 'We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.';
$a->strings['Getting Started'] = 'Getting started';
$a->strings['Friendica Walk-Through'] = 'Friendica walk-through';
$a->strings['On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.'] = 'On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.';
$a->strings['Go to Your Settings'] = 'Go to your settings';
$a->strings['On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.'] = 'On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.';
$a->strings['Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.'] = 'Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.';
$a->strings['Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.'] = 'Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.';
$a->strings['Edit Your Profile'] = 'Edit your profile';
$a->strings['Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.'] = 'Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.';
$a->strings['Profile Keywords'] = 'Profile keywords';
$a->strings['Connecting'] = 'Connecting';
$a->strings['Importing Emails'] = 'Importing emails';
$a->strings['Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX'] = 'Enter your email access information on your Connector Settings if you wish to import and interact with friends or mailing lists from your email INBOX';
$a->strings['Go to Your Contacts Page'] = 'Go to your contacts page';
$a->strings['Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.'] = 'Your contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add new contact</em> dialog.';
$a->strings['Go to Your Site\'s Directory'] = 'Go to your site\'s directory';
$a->strings['The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.'] = 'The directory lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own identity address when requested.';
$a->strings['Finding New People'] = 'Finding new people';
$a->strings['On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.'] = 'On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.';
$a->strings['Why Aren\'t My Posts Public?'] = 'Why aren\'t my posts public?';
$a->strings['Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.'] = 'Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.';
$a->strings['Getting Help'] = 'Getting help';
$a->strings['Go to the Help Section'] = 'Go to the help section';
$a->strings['Our <strong>help</strong> pages may be consulted for detail on other program features and resources.'] = 'Our <strong>help</strong> pages may be consulted for detail on other program features and resources.';
$a->strings['%s liked %s\'s post'] = '%s liked %s\'s post';
$a->strings['%s disliked %s\'s post'] = '%s disliked %s\'s post';
$a->strings['%s is attending %s\'s event'] = '%s is going to %s\'s event';
$a->strings['%s is not attending %s\'s event'] = '%s is not going to %s\'s event';
$a->strings['%s is now friends with %s'] = '%s is now friends with %s';
$a->strings['%s commented on %s\'s post'] = '%s commented on %s\'s post';
$a->strings['%s created a new post'] = '%s posted something new';
$a->strings['Friend Suggestion'] = 'Friend suggestion';
$a->strings['Friend/Connect Request'] = 'Friend/Contact request';
$a->strings['New Follower'] = 'New follower';
$a->strings['%1$s sent you a new private message at %2$s.'] = '%1$s sent you a new private message at %2$s.';
$a->strings['a private message'] = 'a private message';
$a->strings['%1$s sent you %2$s.'] = '%1$s sent you %2$s.';
$a->strings['Please visit %s to view and/or reply to your private messages.'] = 'Please visit %s to view or reply to your private messages.';
$a->strings['%s commented on an item/conversation you have been following.'] = '%s commented on an item/conversation you have been following.';
$a->strings['Please visit %s to view and/or reply to the conversation.'] = 'Please visit %s to view or reply to the conversation.';
$a->strings['%1$s posted to your profile wall at %2$s'] = '%1$s posted to your profile wall at %2$s';
$a->strings['%1$s posted to [url=%2$s]your wall[/url]'] = '%1$s posted to [url=%2$s]your wall[/url]';
$a->strings['You\'ve received an introduction from \'%1$s\' at %2$s'] = 'You\'ve received an introduction from \'%1$s\' at %2$s';
$a->strings['You\'ve received [url=%1$s]an introduction[/url] from %2$s.'] = 'You\'ve received [url=%1$s]an introduction[/url] from %2$s.';
$a->strings['You may visit their profile at %s'] = 'You may visit their profile at %s';
$a->strings['Please visit %s to approve or reject the introduction.'] = 'Please visit %s to approve or reject the introduction.';
$a->strings['%1$s is sharing with you at %2$s'] = '%1$s is sharing with you at %2$s';
$a->strings['You have a new follower at %2$s : %1$s'] = 'You have a new follower at %2$s : %1$s';
$a->strings['You\'ve received a friend suggestion from \'%1$s\' at %2$s'] = 'You\'ve received a friend suggestion from \'%1$s\' at %2$s';
$a->strings['You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.'] = 'You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.';
$a->strings['Name:'] = 'Name:';
$a->strings['Photo:'] = 'Photo:';
$a->strings['Please visit %s to approve or reject the suggestion.'] = 'Please visit %s to approve or reject the suggestion.';
$a->strings['\'%1$s\' has accepted your connection request at %2$s'] = '\'%1$s\' has accepted your connection request at %2$s';
$a->strings['%2$s has accepted your [url=%1$s]connection request[/url].'] = '%2$s has accepted your [url=%1$s]connection request[/url].';
$a->strings['You are now mutual friends and may exchange status updates, photos, and email without restriction.'] = 'You are now mutual friends and may exchange status updates, photos, and email without restriction.';
$a->strings['Please visit %s if you wish to make any changes to this relationship.'] = 'Please visit %s if you wish to make any changes to this relationship.';
$a->strings['\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.'] = '\'%1$s\' has chosen to accept you as a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.';
$a->strings['\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.'] = '\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.';
$a->strings['Please visit %s  if you wish to make any changes to this relationship.'] = 'Please visit %s  if you wish to make any changes to this relationship.';
$a->strings['registration request'] = 'registration request';
$a->strings['You\'ve received a registration request from \'%1$s\' at %2$s'] = 'You\'ve received a registration request from \'%1$s\' at %2$s.';
$a->strings['You\'ve received a [url=%1$s]registration request[/url] from %2$s.'] = 'You\'ve received a [url=%1$s]registration request[/url] from %2$s.';
$a->strings['Full Name:	%s
Site Location:	%s
Login Name:	%s (%s)'] = 'Full Name:	%s
Site Location:	%s
Login Name:	%s (%s)';
$a->strings['Please visit %s to approve or reject the request.'] = 'Please visit %s to approve or reject the request.';
$a->strings['This message was sent to you by %s, a member of the Friendica social network.'] = 'This message was sent to you by %s, a member of the Friendica social network.';
$a->strings['You may visit them online at %s'] = 'You may visit them online at %s';
$a->strings['Please contact the sender by replying to this post if you do not wish to receive these messages.'] = 'Please contact the sender by replying to this post if you do not wish to receive these messages.';
$a->strings['%s posted an update.'] = '%s posted an update.';
$a->strings['Private Message'] = 'Private message';
$a->strings['This entry was edited'] = 'This entry was edited';
$a->strings['Edit'] = 'Edit';
$a->strings['Delete globally'] = 'Delete globally';
$a->strings['Remove locally'] = 'Remove locally';
$a->strings['I will attend'] = 'I will attend';
$a->strings['I will not attend'] = 'I will not attend';
$a->strings['I might attend'] = 'I might attend';
$a->strings['%s (Received %s)'] = '%s (Received %s)';
$a->strings['to'] = 'to';
$a->strings['via'] = 'via';
$a->strings['Wall-to-Wall'] = 'Wall-to-wall';
$a->strings['via Wall-To-Wall:'] = 'via wall-to-wall:';
$a->strings['Reply to %s'] = 'Reply to %s';
$a->strings['Notifier task is pending'] = 'Notifier task is pending';
$a->strings['Delivery to remote servers is pending'] = 'Delivery to remote servers is pending';
$a->strings['Delivery to remote servers is underway'] = 'Delivery to remote servers is underway';
$a->strings['Delivery to remote servers is mostly done'] = 'Delivery to remote servers is mostly done';
$a->strings['Delivery to remote servers is done'] = 'Delivery to remote servers is done';
$a->strings['%d comment'] = [
	0 => '%d comment',
	1 => '%d comments',
];
$a->strings['Show more'] = 'Show more';
$a->strings['Show fewer'] = 'Show fewer';
$a->strings['%s is now following %s.'] = '%s is now following %s.';
$a->strings['following'] = 'following';
$a->strings['%s stopped following %s.'] = '%s stopped following %s.';
$a->strings['stopped following'] = 'stopped following';
$a->strings['Login failed.'] = 'Login failed.';
$a->strings['Login failed. Please check your credentials.'] = 'Login failed. Please check your credentials.';
$a->strings['Welcome %s'] = 'Welcome %s';
$a->strings['Please upload a profile photo.'] = 'Please upload a profile photo.';
