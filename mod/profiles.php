<?php


function profiles_init(&$a) {

	nav_set_selected('profiles');

	if(! local_user()) {
		return;
	}

	if(($a->argc > 2) && ($a->argv[1] === "drop") && intval($a->argv[2])) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d AND `is-default` = 0 LIMIT 1",
			intval($a->argv[2]),
			intval(local_user())
		);
		if(! count($r)) {
			notice( t('Profile not found.') . EOL);
			goaway($a->get_baseurl(true) . '/profiles');
			return; // NOTREACHED
		}
		
		check_form_security_token_redirectOnErr('/profiles', 'profile_drop', 't');

		// move every contact using this profile as their default to the user default

		$r = q("UPDATE `contact` SET `profile-id` = (SELECT `profile`.`id` AS `profile-id` FROM `profile` WHERE `profile`.`is-default` = 1 AND `profile`.`uid` = %d LIMIT 1) WHERE `profile-id` = %d AND `uid` = %d ",
			intval(local_user()),
			intval($a->argv[2]),
			intval(local_user())
		);
		$r = q("DELETE FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[2]),
			intval(local_user())
		);
		if($r)
			info( t('Profile deleted.') . EOL);

		goaway($a->get_baseurl(true) . '/profiles');
		return; // NOTREACHED
	}





	if(($a->argc > 1) && ($a->argv[1] === 'new')) {
		
		check_form_security_token_redirectOnErr('/profiles', 'profile_new', 't');

		$r0 = q("SELECT `id` FROM `profile` WHERE `uid` = %d",
			intval(local_user()));
		$num_profiles = count($r0);

		$name = t('Profile-') . ($num_profiles + 1);

		$r1 = q("SELECT `name`, `photo`, `thumb` FROM `profile` WHERE `uid` = %d AND `is-default` = 1 LIMIT 1",
			intval(local_user()));
		
		$r2 = q("INSERT INTO `profile` (`uid` , `profile-name` , `name`, `photo`, `thumb`)
			VALUES ( %d, '%s', '%s', '%s', '%s' )",
			intval(local_user()),
			dbesc($name),
			dbesc($r1[0]['name']),
			dbesc($r1[0]['photo']),
			dbesc($r1[0]['thumb'])
		);

		$r3 = q("SELECT `id` FROM `profile` WHERE `uid` = %d AND `profile-name` = '%s' LIMIT 1",
			intval(local_user()),
			dbesc($name)
		);

		info( t('New profile created.') . EOL);
		if(count($r3) == 1)
			goaway($a->get_baseurl(true) . '/profiles/' . $r3[0]['id']);
		
		goaway($a->get_baseurl(true) . '/profiles');
	} 

	if(($a->argc > 2) && ($a->argv[1] === 'clone')) {
		
		check_form_security_token_redirectOnErr('/profiles', 'profile_clone', 't');

		$r0 = q("SELECT `id` FROM `profile` WHERE `uid` = %d",
			intval(local_user()));
		$num_profiles = count($r0);

		$name = t('Profile-') . ($num_profiles + 1);
		$r1 = q("SELECT * FROM `profile` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval(local_user()),
			intval($a->argv[2])
		);
		if(! count($r1)) {
			notice( t('Profile unavailable to clone.') . EOL);
			killme();
			return;
		}
		unset($r1[0]['id']);
		$r1[0]['is-default'] = 0;
		$r1[0]['publish'] = 0;	
		$r1[0]['net-publish'] = 0;	
		$r1[0]['profile-name'] = dbesc($name);

		dbesc_array($r1[0]);

		$r2 = dbq("INSERT INTO `profile` (`" 
			. implode("`, `", array_keys($r1[0])) 
			. "`) VALUES ('" 
			. implode("', '", array_values($r1[0])) 
			. "')" );

		$r3 = q("SELECT `id` FROM `profile` WHERE `uid` = %d AND `profile-name` = '%s' LIMIT 1",
			intval(local_user()),
			dbesc($name)
		);
		info( t('New profile created.') . EOL);
		if(count($r3) == 1)
			goaway($a->get_baseurl(true) . '/profiles/' . $r3[0]['id']);
		
		goaway($a->get_baseurl(true) . '/profiles');
		
		return; // NOTREACHED
	}


	if(($a->argc > 1) && (intval($a->argv[1]))) {
		$r = q("SELECT id FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if(! count($r)) {
			notice( t('Profile not found.') . EOL);
			killme();
			return;
		}

		profile_load($a,$a->user['nickname'],$r[0]['id']);
	}

}

function profiles_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$namechanged = false;

	call_hooks('profile_post', $_POST);

	if(($a->argc > 1) && ($a->argv[1] !== "new") && intval($a->argv[1])) {
		$orig = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if(! count($orig)) {
			notice( t('Profile not found.') . EOL);
			return;
		}
		
		check_form_security_token_redirectOnErr('/profiles', 'profile_edit');
		
		$is_default = (($orig[0]['is-default']) ? 1 : 0);

		$profile_name = notags(trim($_POST['profile_name']));
		if(! strlen($profile_name)) {
			notify( t('Profile Name is required.') . EOL);
			return;
		}
	
		$year = intval($_POST['year']);
		if($year < 1900 || $year > 2100 || $year < 0)
			$year = 0;
		$month = intval($_POST['month']);
			if(($month > 12) || ($month < 0))
				$month = 0;
		$mtab = array(0,31,29,31,30,31,30,31,31,30,31,30,31);
		$day = intval($_POST['day']);
			if(($day > $mtab[$month]) || ($day < 0))
				$day = 0;

		// It's OK to have an empty (0) year, but if you supplied a year you have to have a non-zero month and day
		if($year && ! $month)
			$month = 1;
		if($year && ! $day)
			$day = 1;

		$dob = '0000-00-00';
		$dob = sprintf('%04d-%02d-%02d',$year,$month,$day);

			
		$name = notags(trim($_POST['name']));

		if(! strlen($name)) {
			$name = '[No Name]';
		}

		if($orig[0]['name'] != $name)
			$namechanged = true;



		$pdesc = notags(trim($_POST['pdesc']));
		$gender = notags(trim($_POST['gender']));
		$address = notags(trim($_POST['address']));
		$locality = notags(trim($_POST['locality']));
		$region = notags(trim($_POST['region']));
		$postal_code = notags(trim($_POST['postal_code']));
		$country_name = notags(trim($_POST['country_name']));
		$pub_keywords = notags(trim($_POST['pub_keywords']));
		$prv_keywords = notags(trim($_POST['prv_keywords']));
		$marital = notags(trim($_POST['marital']));
		$howlong = notags(trim($_POST['howlong']));

		$with = ((x($_POST,'with')) ? notags(trim($_POST['with'])) : '');

		if(! strlen($howlong))
			$howlong = '0000-00-00 00:00:00';
		else
			$howlong = datetime_convert(date_default_timezone_get(),'UTC',$howlong);
 
		// linkify the relationship target if applicable

		$withchanged = false;

		if(strlen($with)) {
			if($with != strip_tags($orig[0]['with'])) {
				$withchanged = true;
				$prf = '';
				$lookup = $with;
				if(strpos($lookup,'@') === 0)
					$lookup = substr($lookup,1);
				$lookup = str_replace('_',' ', $lookup);
				if(strpos($lookup,'@') || (strpos($lookup,'http://'))) {
					$newname = $lookup;
					$links = @lrdd($lookup);
					if(count($links)) {
						foreach($links as $link) {
							if($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page') {
            	       			$prf = $link['@attributes']['href'];
							}
						}
					}
				}
				else {
					$newname = $lookup;
/*					if(strstr($lookup,' ')) {
						$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($newname),
							intval(local_user())
						);
					}
					else {
						$r = q("SELECT * FROM `contact` WHERE `nick` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($lookup),
							intval(local_user())
						);
					}*/
					
					$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($newname),
						intval(local_user())
					);
					if(! $r) {
						$r = q("SELECT * FROM `contact` WHERE `nick` = '%s' AND `uid` = %d LIMIT 1",
							dbesc($lookup),
							intval(local_user())
						);
					}
					if(count($r)) {
						$prf = $r[0]['url'];
						$newname = $r[0]['name'];
					}
				}
	
				if($prf) {
					$with = str_replace($lookup,'<a href="' . $prf . '">' . $newname	. '</a>', $with);
					if(strpos($with,'@') === 0)
						$with = substr($with,1);
				}
			}
			else
				$with = $orig[0]['with'];
		}

		$sexual = notags(trim($_POST['sexual']));
		$homepage = notags(trim($_POST['homepage']));
		if (strpos($homepage, 'http') !== 0) {
		    // neither http nor https in URL, add them
		    $homepage = 'http://'.$homepage;
		}
		$hometown = notags(trim($_POST['hometown']));
		$politic = notags(trim($_POST['politic']));
		$religion = notags(trim($_POST['religion']));

		$likes = fix_mce_lf(escape_tags(trim($_POST['likes'])));
		$dislikes = fix_mce_lf(escape_tags(trim($_POST['dislikes'])));

		$about = fix_mce_lf(escape_tags(trim($_POST['about'])));
		$interest = fix_mce_lf(escape_tags(trim($_POST['interest'])));
		$contact = fix_mce_lf(escape_tags(trim($_POST['contact'])));
		$music = fix_mce_lf(escape_tags(trim($_POST['music'])));
		$book = fix_mce_lf(escape_tags(trim($_POST['book'])));
		$tv = fix_mce_lf(escape_tags(trim($_POST['tv'])));
		$film = fix_mce_lf(escape_tags(trim($_POST['film'])));
		$romance = fix_mce_lf(escape_tags(trim($_POST['romance'])));
		$work = fix_mce_lf(escape_tags(trim($_POST['work'])));
		$education = fix_mce_lf(escape_tags(trim($_POST['education'])));

		$hide_friends = (($_POST['hide-friends'] == 1) ? 1: 0);



		$changes = array();
		$value = '';
		if($is_default) {
			if($marital != $orig[0]['marital']) {
				$changes[] = '[color=#ff0000]&hearts;[/color] ' . t('Marital Status');
				$value = $marital;
			}
			if($withchanged) {
				$changes[] = '[color=#ff0000]&hearts;[/color] ' . t('Romantic Partner');
				$value = strip_tags($with);
			}
			if($likes != $orig[0]['likes']) {
				$changes[] = t('Likes');
				$value = $likes;
			}
			if($dislikes != $orig[0]['dislikes']) {
				$changes[] = t('Dislikes');
				$value = $dislikes;
			}
			if($work != $orig[0]['work']) {
				$changes[] = t('Work/Employment');
			}
			if($religion != $orig[0]['religion']) {
				$changes[] = t('Religion');
				$value = $religion;
			}
			if($politic != $orig[0]['politic']) {
				$changes[] = t('Political Views');
				$value = $politic;
			}
			if($gender != $orig[0]['gender']) {
				$changes[] = t('Gender');
				$value = $gender;
			}
			if($sexual != $orig[0]['sexual']) {
				$changes[] = t('Sexual Preference');
				$value = $sexual;
			}
			if($homepage != $orig[0]['homepage']) {
				$changes[] = t('Homepage');
				$value = $homepage;
			}
			if($interest != $orig[0]['interest']) {
				$changes[] = t('Interests');
				$value = $interest;
			}
			if($address != $orig[0]['address']) {
				$changes[] = t('Address');
				// New address not sent in notifications, potential privacy issues
				// in case this leaks to unintended recipients. Yes, it's in the public
				// profile but that doesn't mean we have to broadcast it to everybody.
			}
			if($locality != $orig[0]['locality'] || $region != $orig[0]['region']
				|| $country_name != $orig[0]['country-name']) {
 				$changes[] = t('Location');
				$comma1 = ((($locality) && ($region || $country_name)) ? ', ' : ' ');
				$comma2 = (($region && $country_name) ? ', ' : '');
				$value = $locality . $comma1 . $region . $comma2 . $country_name;
			}

			profile_activity($changes,$value);

		}			
			
		$r = q("UPDATE `profile` 
			SET `profile-name` = '%s',
			`name` = '%s',
			`pdesc` = '%s',
			`gender` = '%s',
			`dob` = '%s',
			`address` = '%s',
			`locality` = '%s',
			`region` = '%s',
			`postal-code` = '%s',
			`country-name` = '%s',
			`marital` = '%s',
			`with` = '%s',
			`howlong` = '%s',
			`sexual` = '%s',
			`homepage` = '%s',
			`hometown` = '%s',
			`politic` = '%s',
			`religion` = '%s',
			`pub_keywords` = '%s',
			`prv_keywords` = '%s',
			`likes` = '%s',
			`dislikes` = '%s',
			`about` = '%s',
			`interest` = '%s',
			`contact` = '%s',
			`music` = '%s',
			`book` = '%s',
			`tv` = '%s',
			`film` = '%s',
			`romance` = '%s',
			`work` = '%s',
			`education` = '%s',
			`hide-friends` = %d
			WHERE `id` = %d AND `uid` = %d LIMIT 1",
			dbesc($profile_name),
			dbesc($name),
			dbesc($pdesc),
			dbesc($gender),
			dbesc($dob),
			dbesc($address),
			dbesc($locality),
			dbesc($region),
			dbesc($postal_code),
			dbesc($country_name),
			dbesc($marital),
			dbesc($with),
			dbesc($howlong),
			dbesc($sexual),
			dbesc($homepage),
			dbesc($hometown),
			dbesc($politic),
			dbesc($religion),
			dbesc($pub_keywords),
			dbesc($prv_keywords),
			dbesc($likes),
			dbesc($dislikes),
			dbesc($about),
			dbesc($interest),
			dbesc($contact),
			dbesc($music),
			dbesc($book),
			dbesc($tv),
			dbesc($film),
			dbesc($romance),
			dbesc($work),
			dbesc($education),
			intval($hide_friends),
			intval($a->argv[1]),
			intval(local_user())
		);

		if($r)
			info( t('Profile updated.') . EOL);


		if($namechanged && $is_default) {
			$r = q("UPDATE `contact` SET `name-date` = '%s' WHERE `self` = 1 AND `uid` = %d LIMIT 1",
				dbesc(datetime_convert()),
				intval(local_user())
			);
			$r = q("UPDATE `user` set `username` = '%s' where `uid` = %d limit 1",
				dbesc($name),
				intval(local_user())
			);
		}

		if($is_default) {
			// Update global directory in background
			$url = $_SESSION['my_url'];
			if($url && strlen(get_config('system','directory_submit_url')))
				proc_run('php',"include/directory.php","$url");

			require_once('include/profile_update.php');
			profile_change();
		}
	}
}


function profile_activity($changed, $value) {
	$a = get_app();

	if(! local_user() || ! is_array($changed) || ! count($changed))
		return;

	if($a->user['hidewall'] || get_config('system','block_public'))
		return;

	if(! get_pconfig(local_user(),'system','post_profilechange'))
		return;

	require_once('include/items.php');

	$self = q("SELECT * FROM `contact` WHERE `self` = 1 AND `uid` = %d LIMIT 1",
		intval(local_user())
	);

	if(! count($self))
		return;

	$arr = array();
	$arr['uri'] = $arr['parent-uri'] = item_new_uri($a->get_hostname(), local_user()); 
	$arr['uid'] = local_user();
	$arr['contact-id'] = $self[0]['id'];
	$arr['wall'] = 1;
	$arr['type'] = 'wall';
	$arr['gravity'] = 0;
	$arr['origin'] = 1;
	$arr['author-name'] = $arr['owner-name'] = $self[0]['name'];
	$arr['author-link'] = $arr['owner-link'] = $self[0]['url'];
	$arr['author-avatar'] = $arr['owner-avatar'] = $self[0]['thumb'];
	$arr['verb'] = ACTIVITY_UPDATE;
	$arr['object-type'] = ACTIVITY_OBJ_PROFILE;
				
	$A = '[url=' . $self[0]['url'] . ']' . $self[0]['name'] . '[/url]';


	$changes = '';
	$t = count($changed);
	$z = 0;
	foreach($changed as $ch) {
		if(strlen($changes)) {
			if ($z == ($t - 1))
				$changes .= t(' and ');
			else
				$changes .= ', ';
		}
		$z ++;
		$changes .= $ch;
	}

	$prof = '[url=' . $self[0]['url'] . '?tab=profile' . ']' . t('public profile') . '[/url]';	

	if($t == 1 && strlen($value)) {
		$message = sprintf( t('%1$s changed %2$s to &ldquo;%3$s&rdquo;'), $A, $changes, $value);
		$message .= "\n\n" . sprintf( t(' - Visit %1$s\'s %2$s'), $A, $prof);
	}
	else
		$message = 	sprintf( t('%1$s has an updated %2$s, changing %3$s.'), $A, $prof, $changes);
 

	$arr['body'] = $message;  

	$arr['object'] = '<object><type>' . ACTIVITY_OBJ_PROFILE . '</type><title>' . $self[0]['name'] . '</title>'
	. '<id>' . $self[0]['url'] . '/' . $self[0]['name'] . '</id>';
	$arr['object'] .= '<link>' . xmlify('<link rel="alternate" type="text/html" href="' . $self[0]['url'] . '?tab=profile' . '" />' . "\n");
	$arr['object'] .= xmlify('<link rel="photo" type="image/jpeg" href="' . $self[0]['thumb'] . '" />' . "\n");
	$arr['object'] .= '</link></object>' . "\n";
	$arr['last-child'] = 1;

	$arr['allow_cid'] = $a->user['allow_cid'];
	$arr['allow_gid'] = $a->user['allow_gid'];
	$arr['deny_cid']  = $a->user['deny_cid'];
	$arr['deny_gid']  = $a->user['deny_gid'];

	$i = item_store($arr);
	if($i) {

		// give it a permanent link
		q("update item set plink = '%s' where id = %d limit 1",
			dbesc($a->get_baseurl() . '/display/' . $a->user['nickname'] . '/' . $i),
			intval($i)
		);

	   	proc_run('php',"include/notifier.php","activity","$i");

	}
}


function profiles_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$o = '';

	if(($a->argc > 1) && (intval($a->argv[1]))) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if(! count($r)) {
			notice( t('Profile not found.') . EOL);
			return;
		}

		require_once('include/profile_selectors.php');


/*		$editselect = 'textareas';
		if( intval(get_pconfig(local_user(),'system','plaintext')) || !feature_enabled(local_user(),'richtext') )
			$editselect = 'none';*/
		$editselect = 'none';
		if( feature_enabled(local_user(),'richtext') )
			$editselect = 'textareas';

		$a->page['htmlhead'] .= replace_macros(get_markup_template('profed_head.tpl'), array(
			'$baseurl' => $a->get_baseurl(true),
			'$editselect' => $editselect,
		));
		$a->page['end'] .= replace_macros(get_markup_template('profed_end.tpl'), array(
			'$baseurl' => $a->get_baseurl(true),
			'$editselect' => $editselect,
		));


		$opt_tpl = get_markup_template("profile-hide-friends.tpl");
		$hide_friends = replace_macros($opt_tpl,array(
			'$desc' => t('Hide your contact/friend list from viewers of this profile?'),
			'$yes_str' => t('Yes'),
			'$no_str' => t('No'),
			'$yes_selected' => (($r[0]['hide-friends']) ? " checked=\"checked\" " : ""),
			'$no_selected' => (($r[0]['hide-friends'] == 0) ? " checked=\"checked\" " : "")
		));




		$f = get_config('system','birthday_input_format');
		if(! $f)
			$f = 'ymd';

		$is_default = (($r[0]['is-default']) ? 1 : 0);
		$tpl = get_markup_template("profile_edit.tpl");
		$o .= replace_macros($tpl,array(
			'$form_security_token' => get_form_security_token("profile_edit"),
			'$profile_clone_link' => 'profiles/clone/' . $r[0]['id'] . '?t=' . get_form_security_token("profile_clone"),
			'$profile_drop_link' => 'profiles/drop/' . $r[0]['id'] . '?t=' . get_form_security_token("profile_drop"),
			'$banner' => t('Edit Profile Details'),
			'$submit' => t('Submit'),
			'$profpic' => t('Change Profile Photo'),
			'$viewprof' => t('View this profile'),
			'$cr_prof' => t('Create a new profile using these settings'),
			'$cl_prof' => t('Clone this profile'),
			'$del_prof' => t('Delete this profile'),
			'$lbl_profname' => t('Profile Name:'),
			'$lbl_fullname' => t('Your Full Name:'),
			'$lbl_title' => t('Title/Description:'),
			'$lbl_gender' => t('Your Gender:'),
			'$lbl_bd' => sprintf( t("Birthday \x28%s\x29:"),datesel_format($f)),
			'$lbl_address' => t('Street Address:'),
			'$lbl_city' => t('Locality/City:'),
			'$lbl_zip' => t('Postal/Zip Code:'),
			'$lbl_country' => t('Country:'),
			'$lbl_region' => t('Region/State:'),
			'$lbl_marital' => t('<span class="heart">&hearts;</span> Marital Status:'),
			'$lbl_with' => t("Who: \x28if applicable\x29"),
			'$lbl_ex1' => t('Examples: cathy123, Cathy Williams, cathy@example.com'),
			'$lbl_howlong' => t('Since [date]:'),
			'$lbl_sexual' => t('Sexual Preference:'),
			'$lbl_homepage' => t('Homepage URL:'),
			'$lbl_hometown' => t('Hometown:'),
			'$lbl_politic' => t('Political Views:'),
			'$lbl_religion' => t('Religious Views:'),
			'$lbl_pubkey' => t('Public Keywords:'),
			'$lbl_prvkey' => t('Private Keywords:'),
			'$lbl_likes' => t('Likes:'),
			'$lbl_dislikes' => t('Dislikes:'),
			'$lbl_ex2' => t('Example: fishing photography software'),
			'$lbl_pubdsc' => t("\x28Used for suggesting potential friends, can be seen by others\x29"),
			'$lbl_prvdsc' => t("\x28Used for searching profiles, never shown to others\x29"),
			'$lbl_about' => t('Tell us about yourself...'),
			'$lbl_hobbies' => t('Hobbies/Interests'),
			'$lbl_social' => t('Contact information and Social Networks'),
			'$lbl_music' => t('Musical interests'),
			'$lbl_book' => t('Books, literature'),
			'$lbl_tv' => t('Television'),
			'$lbl_film' => t('Film/dance/culture/entertainment'),
			'$lbl_love' => t('Love/romance'),
			'$lbl_work' => t('Work/employment'),
			'$lbl_school' => t('School/education'),
			'$disabled' => (($is_default) ? 'onclick="return false;" style="color: #BBBBFF;"' : ''),
			'$baseurl' => $a->get_baseurl(true),
			'$profile_id' => $r[0]['id'],
			'$profile_name' => $r[0]['profile-name'],
			'$default' => (($is_default) ? '<p id="profile-edit-default-desc">' . t('This is your <strong>public</strong> profile.<br />It <strong>may</strong> be visible to anybody using the internet.') . '</p>' : ""),
			'$name' => $r[0]['name'],
			'$pdesc' => $r[0]['pdesc'],
			'$dob' => dob($r[0]['dob']),
			'$hide_friends' => $hide_friends,
			'$address' => $r[0]['address'],
			'$locality' => $r[0]['locality'],
			'$region' => $r[0]['region'],
			'$postal_code' => $r[0]['postal-code'],
			'$country_name' => $r[0]['country-name'],
			'$age' => ((intval($r[0]['dob'])) ? '(' . t('Age: ') . age($r[0]['dob'],$a->user['timezone'],$a->user['timezone']) . ')' : ''),
			'$gender' => gender_selector($r[0]['gender']),
			'$marital' => marital_selector($r[0]['marital']),
			'$with' => strip_tags($r[0]['with']),
			'$howlong' => ($r[0]['howlong'] === '0000-00-00 00:00:00' ? '' : datetime_convert('UTC',date_default_timezone_get(),$r[0]['howlong'])),
			'$sexual' => sexpref_selector($r[0]['sexual']),
			'$about' => $r[0]['about'],
			'$homepage' => $r[0]['homepage'],
			'$hometown' => $r[0]['hometown'],
			'$politic' => $r[0]['politic'],
			'$religion' => $r[0]['religion'],
			'$pub_keywords' => $r[0]['pub_keywords'],
			'$prv_keywords' => $r[0]['prv_keywords'],
			'$likes' => $r[0]['likes'],
			'$dislikes' => $r[0]['dislikes'],
			'$music' => $r[0]['music'],
			'$book' => $r[0]['book'],
			'$tv' => $r[0]['tv'],
			'$film' => $r[0]['film'],
			'$interest' => $r[0]['interest'],
			'$romance' => $r[0]['romance'],
			'$work' => $r[0]['work'],
			'$education' => $r[0]['education'],
			'$contact' => $r[0]['contact']
		));

		$arr = array('profile' => $r[0], 'entry' => $o);
		call_hooks('profile_edit', $arr);

		return $o;
	}
	else {

		$r = q("SELECT * FROM `profile` WHERE `uid` = %d",
			local_user());
		if(count($r)) {

			$tpl_header = get_markup_template('profile_listing_header.tpl');
			$o .= replace_macros($tpl_header,array(
				'$header' => t('Edit/Manage Profiles'),
				'$chg_photo' => t('Change profile photo'),
				'$cr_new' => t('Create New Profile'),
				'$cr_new_link' => 'profiles/new?t=' . get_form_security_token("profile_new")
			));


			$tpl = get_markup_template('profile_entry.tpl');

			foreach($r as $rr) {
				$o .= replace_macros($tpl, array(
					'$photo' => $a->get_cached_avatar_image($rr['thumb']),
					'$id' => $rr['id'],
					'$alt' => t('Profile Image'),
					'$profile_name' => $rr['profile-name'],
					'$visible' => (($rr['is-default']) ? '<strong>' . t('visible to everybody') . '</strong>' 
						: '<a href="' . $a->get_baseurl(true) . '/profperm/' . $rr['id'] . '" />' . t('Edit visibility') . '</a>')
				));
			}
		}
		return $o;
	}

}
