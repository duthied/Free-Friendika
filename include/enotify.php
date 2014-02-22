<?php

require_once('include/email.php');

function notification($params) {

	logger('notification: entry', LOGGER_DEBUG);

	$a = get_app();

	// from here on everything is in the recipients language

	push_lang($params['language']);


	$banner = t('Friendica Notification');
	$product = FRIENDICA_PLATFORM;
	$siteurl = $a->get_baseurl(true);
	$thanks = t('Thank You,');
	$sitename = $a->config['sitename'];
	$site_admin = sprintf( t('%s Administrator'), $sitename);

	$sender_name = $product;
	$hostname = $a->get_hostname();
	if(strpos($hostname,':'))
		$hostname = substr($hostname,0,strpos($hostname,':'));

	$sender_email = t('noreply') . '@' . $hostname;
	$additional_mail_header = "";

	if(array_key_exists('item',$params)) {
		$title = $params['item']['title'];
		$body = $params['item']['body'];
	}
	else {
		$title = $body = '';
	}

	// e.g. "your post", "David's photo", etc.
	$possess_desc = t('%s <!item_type!>');

	if($params['type'] == NOTIFY_MAIL) {

		$subject = 	sprintf( t('[Friendica:Notify] New mail received at %s'),$sitename);

		$preamble = sprintf( t('%1$s sent you a new private message at %2$s.'),$params['source_name'],$sitename);
		$epreamble = sprintf( t('%1$s sent you %2$s.'),'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]', '[url=$itemlink]' . t('a private message') . '[/url]');
		$sitelink = t('Please visit %s to view and/or reply to your private messages.');
		$tsitelink = sprintf( $sitelink, $siteurl . '/message/' . $params['item']['id'] );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '/message/' . $params['item']['id'] . '">' . $sitename . '</a>');
		$itemlink = $siteurl . '/message/' . $params['item']['id'];
	}

	if($params['type'] == NOTIFY_COMMENT) {
//		logger("notification: params = " . print_r($params, true), LOGGER_DEBUG);

		$parent_id = $params['parent'];

		// Check to see if there was already a tag notify or comment notify for this post.
		// If so don't create a second notification

		$p = null;
		$p = q("select id from notify where (type = %d or type = %d or type = %d) and link = '%s' and uid = %d limit 1",
			intval(NOTIFY_TAGSELF),
			intval(NOTIFY_COMMENT),
			intval(NOTIFY_SHARE),
			dbesc($params['link']),
			intval($params['uid'])
		);
		if($p and count($p)) {
			pop_lang();
			return;
		}


		// if it's a post figure out who's post it is.

		$p = null;

		if($params['otype'] === 'item' && $parent_id) {
			$p = q("select * from item where id = %d and uid = %d limit 1",
				intval($parent_id),
				intval($params['uid'])
			);
		}

		$item_post_type = item_post_type($p[0]);
		//$possess_desc = str_replace('<!item_type!>',$possess_desc);

		// "a post"
		$dest_str = sprintf(t('%1$s commented on [url=%2$s]a %3$s[/url]'),
								'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]',
								$itemlink,
								$item_post_type);

		// "George Bull's post"
		if($p)
			$dest_str = sprintf(t('%1$s commented on [url=%2$s]%3$s\'s %4$s[/url]'),
						'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]',
						$itemlink,
						$p[0]['author-name'],
						$item_post_type);

		// "your post"
		if($p[0]['owner-name'] == $p[0]['author-name'] && $p[0]['wall'])
			$dest_str = sprintf(t('%1$s commented on [url=%2$s]your %3$s[/url]'),
								'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]',
								$itemlink,
								$item_post_type);

		// Some mail softwares relies on subject field for threading.
		// So, we cannot have different subjects for notifications of the same thread.
		// Before this we have the name of the replier on the subject rendering 
		// differents subjects for messages on the same thread.

		$subject = sprintf( t('[Friendica:Notify] Comment to conversation #%1$d by %2$s'), $parent_id, $params['source_name']);
		$preamble = sprintf( t('%s commented on an item/conversation you have been following.'), $params['source_name']); 
		$epreamble = $dest_str;

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_WALL) {
		$subject = sprintf( t('[Friendica:Notify] %s posted to your profile wall') , $params['source_name']);

		$preamble = sprintf( t('%1$s posted to your profile wall at %2$s') , $params['source_name'], $sitename);

		$epreamble = sprintf( t('%1$s posted to [url=%2$s]your wall[/url]') ,
								'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]',
								$params['link']);

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_TAGSELF) {
		$subject =	sprintf( t('[Friendica:Notify] %s tagged you') , $params['source_name']);
		$preamble = sprintf( t('%1$s tagged you at %2$s') , $params['source_name'], $sitename);
		$epreamble = sprintf( t('%1$s [url=%2$s]tagged you[/url].') ,
								'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]',
								$params['link']);

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_SHARE) {
		$subject =	sprintf( t('[Friendica:Notify] %s shared a new post') , $params['source_name']);
		$preamble = sprintf( t('%1$s shared a new post at %2$s') , $params['source_name'], $sitename);
		$epreamble = sprintf( t('%1$s [url=%2$s]shared a post[/url].') ,
								'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]',
								$params['link']);

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_POKE) {

		$subject =	sprintf( t('[Friendica:Notify] %1$s poked you') , $params['source_name']);
		$preamble = sprintf( t('%1$s poked you at %2$s') , $params['source_name'], $sitename);
		$epreamble = sprintf( t('%1$s [url=%2$s]poked you[/url].') , 
								'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]',
								$params['link']); 

		$subject = str_replace('poked', t($params['activity']), $subject);
		$preamble = str_replace('poked', t($params['activity']), $preamble);
		$epreamble = str_replace('poked', t($params['activity']), $epreamble);

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_TAGSHARE) {
		$subject =	sprintf( t('[Friendica:Notify] %s tagged your post') , $params['source_name']);
		$preamble = sprintf( t('%1$s tagged your post at %2$s') , $params['source_name'], $sitename);
		$epreamble = sprintf( t('%1$s tagged [url=%2$s]your post[/url]') ,
								'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]',
								$itemlink); 

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_INTRO) {
		$subject = sprintf( t('[Friendica:Notify] Introduction received'));
		$preamble = sprintf( t('You\'ve received an introduction from \'%1$s\' at %2$s'), $params['source_name'], $sitename); 
		$epreamble = sprintf( t('You\'ve received [url=%1$s]an introduction[/url] from %2$s.'),
								$itemlink,
								'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]'); 
		$body = sprintf( t('You may visit their profile at %s'),$params['source_link']);

		$sitelink = t('Please visit %s to approve or reject the introduction.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_SUGGEST) {
		$subject = sprintf( t('[Friendica:Notify] Friend suggestion received'));
		$preamble = sprintf( t('You\'ve received a friend suggestion from \'%1$s\' at %2$s'), $params['source_name'], $sitename); 
		$epreamble = sprintf( t('You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.'),
									$itemlink,
									'[url=' . $params['item']['url'] . ']' . $params['item']['name'] . '[/url]',
									'[url=' . $params['source_link'] . ']' . $params['source_name'] . '[/url]'); 
									
		$body = t('Name:') . ' ' . $params['item']['name'] . "\n";
		$body .= t('Photo:') . ' ' . $params['item']['photo'] . "\n";
		$body .= sprintf( t('You may visit their profile at %s'),$params['item']['url']);

		$sitelink = t('Please visit %s to approve or reject the suggestion.');
		$tsitelink = sprintf( $sitelink, $siteurl );
		$hsitelink = sprintf( $sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink =  $params['link'];
	}

	if($params['type'] == NOTIFY_CONFIRM) {
	}

	if($params['type'] == NOTIFY_SYSTEM) {

	}

	$h = array(
		'params'    => $params, 
		'subject'   => $subject,
		'preamble'  => $preamble, 
		'epreamble' => $epreamble, 
		'body'      => $body, 
		'sitelink'  => $sitelink,
		'tsitelink' => $tsitelink,
		'hsitelink' => $hsitelink,
		'itemlink'  => $itemlink
	);
		
	call_hooks('enotify',$h);

	$subject   = $h['subject'];
	$preamble  = $h['preamble'];
	$epreamble = $h['epreamble'];
	$body      = $h['body'];
	$sitelink  = $h['sitelink'];
	$tsitelink = $h['tsitelink'];
	$hsitelink = $h['hsitelink'];
	$itemlink  = $h['itemlink']; 


	require_once('include/html2bbcode.php');	

	do {
		$dups = false;
		$hash = random_string();
        $r = q("SELECT `id` FROM `notify` WHERE `hash` = '%s' LIMIT 1",
			dbesc($hash));
		if(count($r))
			$dups = true;
	} while($dups == true);


	$datarray = array();
	$datarray['hash']  = $hash;
	$datarray['name']  = $params['source_name'];
	$datarray['url']   = $params['source_link'];
	$datarray['photo'] = $params['source_photo'];
	$datarray['date']  = datetime_convert();
	$datarray['uid']   = $params['uid'];
	$datarray['link']  = $itemlink;
	$datarray['parent'] = $parent_id;
	$datarray['type']  = $params['type'];
	$datarray['verb']  = $params['verb'];
	$datarray['otype'] = $params['otype'];
	$datarray['abort'] = false;
 
	call_hooks('enotify_store', $datarray);

	if($datarray['abort']) {
		pop_lang();
		return;
	}

	// create notification entry in DB

	$r = q("insert into notify (hash,name,url,photo,date,uid,link,parent,type,verb,otype)
		values('%s','%s','%s','%s','%s',%d,'%s',%d,%d,'%s','%s')",
		dbesc($datarray['hash']),
		dbesc($datarray['name']),
		dbesc($datarray['url']),
		dbesc($datarray['photo']),
		dbesc($datarray['date']),
		intval($datarray['uid']),
		dbesc($datarray['link']),
		intval($datarray['parent']),
		intval($datarray['type']),
		dbesc($datarray['verb']),
		dbesc($datarray['otype'])
	);

	$r = q("select id from notify where hash = '%s' and uid = %d limit 1",
		dbesc($hash),
		intval($params['uid'])
	);
	if($r)
		$notify_id = $r[0]['id'];
	else {
		pop_lang();
		return;
	}

	// we seem to have a lot of duplicate comment notifications due to race conditions, mostly from forums
	// After we've stored everything, look again to see if there are any duplicates and if so remove them

	$p = null;
	$p = q("select id from notify where ( type = %d or type = %d ) and link = '%s' and uid = %d order by id",
		intval(NOTIFY_TAGSELF),
		intval(NOTIFY_COMMENT),
		dbesc($params['link']),
		intval($params['uid'])
	);
	if($p && (count($p) > 1)) {
		for ($d = 1; $d < count($p); $d ++) {
			q("delete from notify where id = %d limit 1",
				intval($p[$d]['id'])
			);
		}

		// only continue on if we stored the first one

		if($notify_id != $p[0]['id']) {
			pop_lang();
			return;
		}
	}








	$itemlink = $a->get_baseurl() . '/notify/view/' . $notify_id;
	$msg = replace_macros($epreamble,array('$itemlink' => $itemlink));
	$r = q("update notify set msg = '%s' where id = %d and uid = %d limit 1",
		dbesc($msg),
		intval($notify_id),
		intval($params['uid'])
	);
		

	// send email notification if notification preferences permit

	require_once('include/bbcode.php');
	if((intval($params['notify_flags']) & intval($params['type'])) || $params['type'] == NOTIFY_SYSTEM) {

		logger('notification: sending notification email');

		$id_for_parent = "${params['parent']}@${hostname}";

		// Is this the first email notification for this parent item and user?
		
		$r = q("select `id` from `notify-threads` where `master-parent-item` = %d and `receiver-uid` = %d limit 1", 
			intval($params['parent']),
			intval($params['uid']) );

		// If so, create the record of it and use a message-id smtp header.

		if(!$r) {
			logger("notify_id:" . intval($notify_id). ", parent: " . intval($params['parent']) . "uid: " . 
intval($params['uid']), LOGGER_DEBUG);
			$r = q("insert into `notify-threads` (`notify-id`, `master-parent-item`, `receiver-uid`, `parent-item`)
				values(%d,%d,%d,%d)",
				intval($notify_id),
				intval($params['parent']),
				intval($params['uid']), 
				0 );

			$additional_mail_header .= "Message-ID: <${id_for_parent}>\n";
			$log_msg = "include/enotify: No previous notification found for this parent:\n" . 
					"  parent: ${params['parent']}\n" . "  uid   : ${params['uid']}\n";
			logger($log_msg, LOGGER_DEBUG);
		}

		// If not, just "follow" the thread.

		else {
			$additional_mail_header = "References: <${id_for_parent}>\nIn-Reply-To: <${id_for_parent}>\n";
			logger("include/enotify: There's already a notification for this parent:\n" . print_r($r, true), LOGGER_DEBUG);
		}



		$textversion = strip_tags(html_entity_decode(bbcode(stripslashes(str_replace(array("\\r\\n", "\\r", "\\n"), "\n",
			$body))),ENT_QUOTES,'UTF-8'));
		$htmlversion = html_entity_decode(bbcode(stripslashes(str_replace(array("\\r\\n", "\\r","\\n\\n" ,"\\n"), 
			"<br />\n",$body))));

		$datarray = array();
		$datarray['banner'] = $banner;
		$datarray['product'] = $product;
		$datarray['preamble'] = $preamble;
		$datarray['sitename'] = $sitename;
		$datarray['siteurl'] = $siteurl;
		$datarray['type'] = $params['type'];
		$datarray['parent'] = $params['parent'];
		$datarray['source_name'] = $params['source_name'];
		$datarray['source_link'] = $params['source_link'];
		$datarray['source_photo'] = $params['source_photo'];
		$datarray['uid'] = $params['uid'];
		$datarray['username'] = $params['to_name'];
		$datarray['hsitelink'] = $hsitelink;
		$datarray['tsitelink'] = $tsitelink;
		$datarray['hitemlink'] = '<a href="' . $itemlink . '">' . $itemlink . '</a>';
		$datarray['titemlink'] = $itemlink;
		$datarray['thanks'] = $thanks;
		$datarray['site_admin'] = $site_admin;
		$datarray['title'] = stripslashes($title);
		$datarray['htmlversion'] = $htmlversion;
		$datarray['textversion'] = $textversion;
		$datarray['subject'] = $subject;
		$datarray['headers'] = $additional_mail_header;

		call_hooks('enotify_mail', $datarray);

		// check whether sending post content in email notifications is allowed
		$content_allowed = !get_config('system','enotify_no_content');

		// load the template for private message notifications
		$tpl = get_markup_template('email_notify_html.tpl');
		$email_html_body = replace_macros($tpl,array(
			'$banner'       => $datarray['banner'],
			'$product'      => $datarray['product'],
			'$preamble'     => $datarray['preamble'],
			'$sitename'     => $datarray['sitename'],
			'$siteurl'      => $datarray['siteurl'],
			'$source_name'  => $datarray['source_name'],
			'$source_link'  => $datarray['source_link'],
			'$source_photo' => $datarray['source_photo'],
			'$username'     => $datarray['to_name'],
			'$hsitelink'    => $datarray['hsitelink'],
			'$hitemlink'    => $datarray['hitemlink'],
			'$thanks'       => $datarray['thanks'],
			'$site_admin'   => $datarray['site_admin'],
			'$title'		=> $datarray['title'],
			'$htmlversion'	=> $datarray['htmlversion'],
			'$content_allowed'	=> $content_allowed,
		));
		
		// load the template for private message notifications
		$tpl = get_markup_template('email_notify_text.tpl');
		$email_text_body = replace_macros($tpl,array(
			'$banner'       => $datarray['banner'],
			'$product'      => $datarray['product'],
			'$preamble'     => $datarray['preamble'],
			'$sitename'     => $datarray['sitename'],
			'$siteurl'      => $datarray['siteurl'],
			'$source_name'  => $datarray['source_name'],
			'$source_link'  => $datarray['source_link'],
			'$source_photo' => $datarray['source_photo'],
			'$username'     => $datarray['to_name'],
			'$tsitelink'    => $datarray['tsitelink'],
			'$titemlink'    => $datarray['titemlink'],
			'$thanks'       => $datarray['thanks'],
			'$site_admin'   => $datarray['site_admin'],
			'$title'		=> $datarray['title'],
			'$textversion'	=> $datarray['textversion'],	
			'$content_allowed'	=> $content_allowed,
		));

//		logger('text: ' . $email_text_body);

		// use the EmailNotification library to send the message

		enotify::send(array(
			'fromName' => $sender_name,
			'fromEmail' => $sender_email,
			'replyTo' => $sender_email,
			'toEmail' => $params['to_email'],
			'messageSubject' => $datarray['subject'],
			'htmlVersion' => $email_html_body,
			'textVersion' => $email_text_body,
			'additionalMailHeader' => $datarray['headers'],
		));
	}

	pop_lang();

}

require_once('include/email.php');

class enotify {
	/**
	 * Send a multipart/alternative message with Text and HTML versions
	 *
	 * @param fromName			name of the sender
	 * @param fromEmail			email fo the sender
	 * @param replyTo			replyTo address to direct responses
	 * @param toEmail			destination email address
	 * @param messageSubject	subject of the message
	 * @param htmlVersion		html version of the message
	 * @param textVersion		text only version of the message
	 * @param additionalMailHeader	additions to the smtp mail header
	 */
	static public function send($params) {

		$fromName = email_header_encode(html_entity_decode($params['fromName'],ENT_QUOTES,'UTF-8'),'UTF-8'); 
		$messageSubject = email_header_encode(html_entity_decode($params['messageSubject'],ENT_QUOTES,'UTF-8'),'UTF-8');
		
		// generate a mime boundary
		$mimeBoundary   =rand(0,9)."-"
				.rand(10000000000,9999999999)."-"
				.rand(10000000000,9999999999)."=:"
				.rand(10000,99999);

		// generate a multipart/alternative message header
		$messageHeader =
			$params['additionalMailHeader'] .
			"From: $fromName <{$params['fromEmail']}>\n" . 
			"Reply-To: $fromName <{$params['replyTo']}>\n" .
			"MIME-Version: 1.0\n" .
			"Content-Type: multipart/alternative; boundary=\"{$mimeBoundary}\"";

		// assemble the final multipart message body with the text and html types included
		$textBody	=	chunk_split(base64_encode($params['textVersion']));
		$htmlBody	=	chunk_split(base64_encode($params['htmlVersion']));
		$multipartMessageBody =
			"--" . $mimeBoundary . "\n" .					// plain text section
			"Content-Type: text/plain; charset=UTF-8\n" .
			"Content-Transfer-Encoding: base64\n\n" .
			$textBody . "\n" .
			"--" . $mimeBoundary . "\n" .					// text/html section
			"Content-Type: text/html; charset=UTF-8\n" .
			"Content-Transfer-Encoding: base64\n\n" .
			$htmlBody . "\n" .
			"--" . $mimeBoundary . "--\n";					// message ending

		// send the message
		$res = mail(
			$params['toEmail'],	 									// send to address
			$messageSubject,								// subject
			$multipartMessageBody,	 						// message body
			$messageHeader									// message headers
		);
		logger("notification: enotify::send header " . 'To: ' . $params['toEmail'] . "\n" . $messageHeader, LOGGER_DEBUG);
		logger("notification: enotify::send returns " . $res, LOGGER_DEBUG);
	}
}
?>
