<?php

if(! function_exists("string_plural_select_gd")) {
function string_plural_select_gd($n){
	$n = intval($n);
	if (($n==1 || $n==11)) { return 0; } else if (($n==2 || $n==12)) { return 1; } else if (($n > 2 && $n < 20)) { return 2; } else  { return 3; }
}}
$a->strings['Access denied.'] = 'Chaidh inntrigeadh a dhiùltadh.';
$a->strings['User not found.'] = 'Cha deach an cleachdaiche a lorg.';
$a->strings['Access to this profile has been restricted.'] = 'Chaidh an t-inntrigeadh dhan phròifil seo a chuingeachadh.';
$a->strings['Events'] = 'Tachartasan';
$a->strings['View'] = 'Seall';
$a->strings['Previous'] = 'Air ais';
$a->strings['Next'] = 'Air adhart';
$a->strings['today'] = 'an-diugh';
$a->strings['month'] = 'mìos';
$a->strings['week'] = 'seachdain';
$a->strings['day'] = 'latha';
$a->strings['list'] = 'liosta';
$a->strings['User not found'] = 'Cha deach an cleachdaiche a lorg';
$a->strings['This calendar format is not supported'] = 'Chan eil taic ri fòrmat a’ mhìosachain seo';
$a->strings['No exportable data found'] = 'Cha deach dàta a ghabhas às-phortadh a lorg';
$a->strings['calendar'] = 'mìosachan';
$a->strings['Public access denied.'] = 'Chaidh an t-inntrigeadh poblach a dhiùltadh.';
$a->strings['The requested item doesn\'t exist or has been deleted.'] = 'Chan eil am an nì a dh’iarr thu ann no chaidh a sguabadh às.';
$a->strings['The feed for this item is unavailable.'] = 'Chan eil inbhir ri fhaighinn dhan nì seo.';
$a->strings['Permission denied.'] = 'Chaidh cead a dhiùltadh.';
$a->strings['Item not found'] = 'Cha deach an nì a lorg';
$a->strings['Edit post'] = 'Deasaich am post';
$a->strings['Save'] = 'Sàbhail';
$a->strings['Loading...'] = '’Ga luchdadh…';
$a->strings['Upload photo'] = 'Luchdaich suas dealbh';
$a->strings['upload photo'] = 'luchdaich suas dealbh';
$a->strings['Attach file'] = 'Ceangail faidhle ris';
$a->strings['attach file'] = 'ceangail faidhle ris';
$a->strings['Insert web link'] = 'Cuir a-steach ceangal-lìn';
$a->strings['web link'] = 'ceangal-lìn';
$a->strings['Insert video link'] = 'Cuir a-steach ceangal video';
$a->strings['video link'] = 'ceangal video';
$a->strings['Insert audio link'] = 'Cuir a-steach ceangal fuaime';
$a->strings['audio link'] = 'ceangal fuaime';
$a->strings['Set your location'] = 'Suidhich d’ ionad';
$a->strings['set location'] = 'suidhich d’ ionad';
$a->strings['Clear browser location'] = 'Falamhaich ionad a’ bhrabhsair';
$a->strings['clear location'] = 'falamhaich an ionad';
$a->strings['Please wait'] = 'Fuirich ort';
$a->strings['Permission settings'] = 'Roghainnean cead';
$a->strings['CC: email addresses'] = 'CC: seòlaidhean puist-d';
$a->strings['Public post'] = 'Post poblach';
$a->strings['Set title'] = 'Suidhich an tiotal';
$a->strings['Categories (comma-separated list)'] = 'Roinnean-seòrsa (liosta sgaraichte le cromagan).';
$a->strings['Example: bob@example.com, mary@example.com'] = 'Mar eisimpleir: aonghas@ball-eisimpleir.com, oighrig@ball-eisimpleir.com';
$a->strings['Preview'] = 'Ro-sheall';
$a->strings['Cancel'] = 'Sguir dheth';
$a->strings['Bold'] = 'Trom';
$a->strings['Italic'] = 'Eadailteach';
$a->strings['Underline'] = 'Loidhne fodha';
$a->strings['Quote'] = 'Iomradh';
$a->strings['Code'] = 'Còd';
$a->strings['Link'] = 'Ceangal';
$a->strings['Link or Media'] = 'Ceangal no meadhan';
$a->strings['Message'] = 'Teachdaireachd';
$a->strings['Browser'] = 'Brabhsair';
$a->strings['Permissions'] = 'Ceadan';
$a->strings['Open Compose page'] = 'Fosgail duilleag an sgrìobhaidh';
$a->strings['Event can not end before it has started.'] = 'Chan urrainn do thachartas crìochnachadh mus tòisich e.';
$a->strings['Event title and start time are required.'] = 'Tha feum air tiotal is àm tòiseachaidh an tachartais.';
$a->strings['Create New Event'] = 'Cruthaich tachartas ùr';
$a->strings['Event details'] = 'Fiosrachadh an tachartais';
$a->strings['Starting date and Title are required.'] = 'Tha feum air àm tòiseachaidh is tiotal.';
$a->strings['Event Starts:'] = 'Tòisichidh an tachartas:';
$a->strings['Required'] = 'Riatanach';
$a->strings['Finish date/time is not known or not relevant'] = 'Chan eil fhios dè an t-àm crìochnachaidh no chan eil e iomchaidh';
$a->strings['Event Finishes:'] = 'Thig an tachartas gu crìoch:';
$a->strings['Description:'] = 'Tuairisgeul:';
$a->strings['Location:'] = 'Ionad:';
$a->strings['Title:'] = 'Tiotal:';
$a->strings['Share this event'] = 'Co-roinn an tachartas seo';
$a->strings['Submit'] = 'Cuir a-null';
$a->strings['Basic'] = 'Bunasach';
$a->strings['Advanced'] = 'Adhartach';
$a->strings['Failed to remove event'] = 'Cha deach leinn an tachartas a thoirt air falbh';
$a->strings['Photos'] = 'Dealbhan';
$a->strings['Upload'] = 'Luchdaich suas';
$a->strings['Files'] = 'Faidhlichean';
$a->strings['Submit Request'] = 'Cuir an t-iarrtas a-null';
$a->strings['You already added this contact.'] = 'Chuir thu an neach-aithne seo ris mu thràth.';
$a->strings['The network type couldn\'t be detected. Contact can\'t be added.'] = 'Cha do mhothaich sinn do sheòrsa an lìonraidh. Cha b’ urrainn dhuinn an neach-aithne a chur ris.';
$a->strings['Diaspora support isn\'t enabled. Contact can\'t be added.'] = 'Chan eil taic ri diaspora* an comas. Cha b’ urrainn dhuinn an neach-aithne a chur ris.';
$a->strings['OStatus support is disabled. Contact can\'t be added.'] = 'Chan eil taic ri OStatus an comas. Cha b’ urrainn dhuinn an neach-aithne a chur ris.';
$a->strings['Connect/Follow'] = 'Ceangail ris/Lean air';
$a->strings['Please answer the following:'] = 'Freagair seo:';
$a->strings['Your Identity Address:'] = 'Seòladh do dhearbh-aithne:';
$a->strings['Profile URL'] = 'URL na pròifile';
$a->strings['Tags:'] = 'Tagaichean:';
$a->strings['%s knows you'] = 'Is aithne dha %s thu';
$a->strings['Add a personal note:'] = 'Cuir nòta pearsanta ris:';
$a->strings['Status Messages and Posts'] = 'Teachdaireachdan staid is puist-d';
$a->strings['The contact could not be added.'] = 'Cha b’ urrainn dhuinn an neach-aithne a chur ris.';
$a->strings['Unable to locate original post.'] = 'Cha do lorg sinn am post tùsail.';
$a->strings['Empty post discarded.'] = 'Chaidh post falamh a thilgeil air falbh.';
$a->strings['Post updated.'] = 'Chaidh am post ùrachadh.';
$a->strings['Item wasn\'t stored.'] = 'Cha deach an nì a stòradh.';
$a->strings['Item couldn\'t be fetched.'] = 'Cha d’ fhuair sinn grèim air a nì.';
$a->strings['Item not found.'] = 'Cha deach an nì a lorg.';
$a->strings['No valid account found.'] = 'Cha deach cunntas dligheach a lorg.';
$a->strings['Password reset request issued. Check your email.'] = 'Chaidh ath-shuidheachadh an fhacail-fhaire iarraidh. Thoir sùil air a’ phost-d agad.';
$a->strings['
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email, the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.'] = '
		%1$s, a charaid
			Fhuair sinn iarrtas aig “%2$s” o chionn goirid airson facal-faire a’ chunntais agad
		ath-shuidheachadh. Airson an t-iarrtas seo a dhearbhadh, tagh an ceangal dearbhaidh
		gu h-ìosal no cuir a-steach ann am bàr nan seòladh sa bhrabhsair-lìn agad e.

		MUR an do dh’iarr thu fhèin an t-atharrachadh seo, NA LEAN air a’ cheangal
		a tha ’ga sholar agus leig seachad agus/no sguab às am post-d seo ’s falbhaidh an ùine air an iarrtas a dh’aithghearr.

		Cha dèid am facal-faire agad atharrachadh ach mas urrainn dhuinn dearbhadh gun do
		dh’iarr thu fhèin seo.';
$a->strings['
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s'] = '
		Lean ris a’ cheangal seo a dh’aithghearr a dhearbhadh gur e tusa a bh’ ann:

		%1$s

		Ghaibh thu teachdaireachd eile an uairsin sa bheil am facal-faire ùr.
		’S urrainn dhut am facal-faire sin atharrachadh o dhuilleag roghainnean a’ chunntais agad às dèidh clàradh a-steach.

		Seo am fiosrachadh clàraidh a-steach:

		Ionad na làraich:	%2$s
		Ainm a’ chlàraidh a-steach:	%3$s';
$a->strings['Password reset requested at %s'] = 'Chaidh ath-shuidheachadh an fhacail-fhaire iarraidh aig %s';
$a->strings['Request could not be verified. (You may have previously submitted it.) Password reset failed.'] = 'Cha b’ urrainn dhuinn an t-iarrtas a dhearbhadh. (Dh’fhaoidte gun do chuir thu a-null e cheana.) Dh’fhàillig le ath-shuidheachadh an fhacail-fhaire.';
$a->strings['Request has expired, please make a new one.'] = 'Dh’fhalbh an ùine air an iarrtas, cruthaich fear ùr.';
$a->strings['Forgot your Password?'] = 'Na dhìochuimhnich thu am facal-faire agad?';
$a->strings['Enter your email address and submit to have your password reset. Then check your email for further instructions.'] = 'Cuir a-steach ’s a-null an seòladh puist-d agad airson ath-shuidheachadh an fhacail-fhaire agad. Thoir sùil air na puist-d agad an uairsin airson stiùireadh.';
$a->strings['Nickname or Email: '] = 'Far-ainm no post-d: ';
$a->strings['Reset'] = 'Ath-shuidhich';
$a->strings['Password Reset'] = 'Ath-shuidheachadh facail-fhaire';
$a->strings['Your password has been reset as requested.'] = 'Chaidh am facal-faire agad ath-shuidheachadh.';
$a->strings['Your new password is'] = 'Seo am facal-faire ùr agad:';
$a->strings['Save or copy your new password - and then'] = 'Sàbhail no dèan lethbhreac dhen fhacal-fhaire ùr agad. An uairsin,';
$a->strings['click here to login'] = 'briog an-seo gus clàradh a-steach';
$a->strings['Your password may be changed from the <em>Settings</em> page after successful login.'] = '’S urrainn dhut am facal-faire agad atharrachadh o dhuilleag nan <em>Roghainnean</em> às dèidh a’ chlàraidh a-steach.';
$a->strings['Your password has been reset.'] = 'Chaidh am facal-faire agad ath-shuidheachadh.';
$a->strings['
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records (or change your password immediately to
			something that you will remember).
		'] = '
			%1$s, a charaid,
				Chaidh am facal-faire agad atharrachadh mar a dh’iarr thu. Cùm
			am fiosrachadh seo sna clàran agad (no atharraich am facal-faire agad sa bhad
			gu rudeigin as urrainn dhut cur ’nad chuimhne).
		';
$a->strings['
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		'] = '
			Seo am fiosrachadh clàraidh a-steach agad:

			Ionad na làraich:	%1$s
			Ainm clàraidh a-steach:	%2$s
			Facal-faire:	%3$s

			’S urrainn dhut am facal-faire agad atharrachadh o dhuilleag roghainnean a’ chunntais agad às dèidh a’ chlàraidh a-steach.
		';
$a->strings['Your password has been changed at %s'] = 'Chaidh am facal-faire agad atharrachadh air %s';
$a->strings['No keywords to match. Please add keywords to your profile.'] = 'Chan eil faclan-luirg rim maidseadh ann. Cuir faclan-luirg ris a’ phròifil agad.';
$a->strings['No matches'] = 'Gun mhaids';
$a->strings['Profile Match'] = 'Maidseadh na pròifile';
$a->strings['New Message'] = 'Teachdaireachd ùr';
$a->strings['No recipient selected.'] = 'Cha deach faightear a thaghadh.';
$a->strings['Unable to locate contact information.'] = 'Cha do lorg sinn am fiosrachadh conaltraidh.';
$a->strings['Message could not be sent.'] = 'Cha b’ urrainn dhuinn an teachdaireachd a chur.';
$a->strings['Message collection failure.'] = 'Dh’fhàillig cruinneachadh nan teachdaireachdan.';
$a->strings['Discard'] = 'Tilg air falbh';
$a->strings['Messages'] = 'Teachdaireachdan';
$a->strings['Conversation not found.'] = 'Cha deach an còmhradh a lorg.';
$a->strings['Message was not deleted.'] = 'Cha deach an teachdaireachd a sguabadh às.';
$a->strings['Conversation was not removed.'] = 'Cha deach an còmhradh a thoirt air falbh.';
$a->strings['Please enter a link URL:'] = 'Cuir URL ceangail:';
$a->strings['Send Private Message'] = 'Cuir teachdaireachd phearsanta';
$a->strings['To:'] = 'Gu:';
$a->strings['Subject:'] = 'Cuspair:';
$a->strings['Your message:'] = 'An teachdaireachd agad:';
$a->strings['No messages.'] = 'Chan eil teachdaireachd sam bith ann.';
$a->strings['Message not available.'] = 'Chan eil an teachdaireachd ri fhaighinn.';
$a->strings['Delete message'] = 'Sguab às an teachdaireachd';
$a->strings['D, d M Y - g:i A'] = 'D d M Y – g:ia';
$a->strings['Delete conversation'] = 'Sguab às an còmhradh';
$a->strings['No secure communications available. You <strong>may</strong> be able to respond from the sender\'s profile page.'] = 'Chan eil conaltradh tèarainte ri fhaighinn. <strong>Dh’fhaoidte</strong> gur urrainn dhut freagairt o dhuilleag pròifil an t-seòladair.';
$a->strings['Send Reply'] = 'Cuir an fhreagairt';
$a->strings['Unknown sender - %s'] = 'Seòladair nach aithne dhuinn – %s';
$a->strings['You and %s'] = 'Thusa is %s';
$a->strings['%s and You'] = '%s is thusa';
$a->strings['%d message'] = [
	0 => '%d teachdaireachd',
	1 => '%d theachdaireachd',
	2 => '%d teachdaireachdan',
	3 => '%d teachdaireachd',
];
$a->strings['Personal Notes'] = 'Nòtaichean pearsanta';
$a->strings['Personal notes are visible only by yourself.'] = 'Chan fhaic ach thu fhèin na nòtaichean pearsanta.';
$a->strings['Subscribing to contacts'] = 'Fo-sgrìobhadh air luchd-aithne';
$a->strings['No contact provided.'] = 'Cha deach neach-aithne a thoirt seachad.';
$a->strings['Couldn\'t fetch information for contact.'] = 'Cha d’ fhuair sinn grèim air an fhiosrachadh conaltraidh.';
$a->strings['Couldn\'t fetch friends for contact.'] = 'Cha d’ fhuair sinn grèim air caraidean a chùm conaltraidh.';
$a->strings['Couldn\'t fetch following contacts.'] = 'Cha d’ fhuair sinn grèim air an luchd-aithne a leanas.';
$a->strings['Couldn\'t fetch remote profile.'] = 'Cha d’ fhuair sinn grèim air a’ phròifil chèin.';
$a->strings['Unsupported network'] = 'Lìonra ris nach eil taic';
$a->strings['Done'] = 'Deiseil';
$a->strings['success'] = 'chaidh leis';
$a->strings['failed'] = 'dh’fhàillig leis';
$a->strings['ignored'] = 'chaidh a leigeil seachad';
$a->strings['Keep this window open until done.'] = 'Cùm an uinneag seo fosgailte gus am bi e deiseil.';
$a->strings['Photo Albums'] = 'Pasgain dhealbhan';
$a->strings['Recent Photos'] = 'Dealbhan o chionn goirid';
$a->strings['Upload New Photos'] = 'Luchdaich suas dealbhan ùra';
$a->strings['everybody'] = 'a h-uile duine';
$a->strings['Contact information unavailable'] = 'Chan eil fiosrachadh conaltraidh ri fhaighinn';
$a->strings['Album not found.'] = 'Cha deach an t-albam a lorg.';
$a->strings['Album successfully deleted'] = 'Chaidh an t-albam a sguabadh às';
$a->strings['Album was empty.'] = 'Bha an t-albam falamh.';
$a->strings['Failed to delete the photo.'] = 'Cha b’ urrainn dhuinn an dealbh a sguabadh às.';
$a->strings['a photo'] = 'dealbh';
$a->strings['%1$s was tagged in %2$s by %3$s'] = 'Chuir %3$s %1$s ’na thaga ri %2$s';
$a->strings['Image exceeds size limit of %s'] = 'Tha an dealbh nas motha na tha ceadaichte dhe %s';
$a->strings['Image upload didn\'t complete, please try again'] = 'Cha deach luchdadh suas an deilbh a choileanadh, feuch ris a-rithist';
$a->strings['Image file is missing'] = 'Tha faidhle an deilbh a dhìth';
$a->strings['Server can\'t accept new file upload at this time, please contact your administrator'] = 'Cha ghabh am frithealaiche ri luchdadh suas deilbh ùir aig an àm seo, cuir fios gun rianaire agad';
$a->strings['Image file is empty.'] = 'Tha faidhle an deilbh falamh.';
$a->strings['Unable to process image.'] = 'Cha b’ urrainn dhuinn an dealbh a phròiseasadh.';
$a->strings['Image upload failed.'] = 'Dh’fhàillig le luchdadh suas an deilbh.';
$a->strings['No photos selected'] = 'Cha deach dealbh a thaghadh';
$a->strings['Access to this item is restricted.'] = 'Tha an t-inntrigeadh dhan nì seo cuingichte.';
$a->strings['Upload Photos'] = 'Luchdaich suas dealbhan';
$a->strings['New album name: '] = 'Ainm an albaim ùir: ';
$a->strings['or select existing album:'] = 'no tagh albam a tha ann:';
$a->strings['Do not show a status post for this upload'] = 'Na seall post staide dhan luchdadh suas seo';
$a->strings['Do you really want to delete this photo album and all its photos?'] = 'A bheil thu cinnteach gu bheil thu airson an t-albam seo ’s a h-uile dealbh aige a sguabadh às?';
$a->strings['Delete Album'] = 'Sguab às an t-albam';
$a->strings['Edit Album'] = 'Deasaich an t-albam';
$a->strings['Drop Album'] = 'Thoir air falbh an t-albam';
$a->strings['Show Newest First'] = 'Seall an fheadhainn as ùire an toiseach';
$a->strings['Show Oldest First'] = 'Seall an fheadhainn as sine an toiseach';
$a->strings['View Photo'] = 'Seall an dealbh';
$a->strings['Permission denied. Access to this item may be restricted.'] = 'Chaidh cead a dhiùltadh. Dh’fhaoidte gu bheil an t-inntrigeadh dhan nì seo cuingichte.';
$a->strings['Photo not available'] = 'Chan eil an dealbhan ri fhaighinn';
$a->strings['Do you really want to delete this photo?'] = 'A bheil thu cinnteach gu bheil thu airson an dealbh seo a sguabadh às?';
$a->strings['Delete Photo'] = 'Sguab às an dealbh';
$a->strings['View photo'] = 'Seall an dealbh';
$a->strings['Edit photo'] = 'Deasaich an dealbh';
$a->strings['Delete photo'] = 'Sguab às an dealbh';
$a->strings['Use as profile photo'] = 'Cleachd ’na dhealbh pròifile';
$a->strings['Private Photo'] = 'Dealbh prìobhaideach';
$a->strings['View Full Size'] = 'Seall air a làn-mheud';
$a->strings['Tags: '] = 'Tagaichean: ';
$a->strings['[Select tags to remove]'] = '[Tagh tagaichean gus an toirt air falbh]';
$a->strings['New album name'] = 'Ainm albaim ùir';
$a->strings['Caption'] = 'Fo-thiotal';
$a->strings['Add a Tag'] = 'Cuir taga ris';
$a->strings['Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'] = 'Ball-eisimpleir: @aonghas, @Oighrig_Chaimbeul, @seaonaidh@ball-eisimpleir.com, #Leòdhas, #gàirnealaireachd';
$a->strings['Do not rotate'] = 'Na cuairtich';
$a->strings['Rotate CW (right)'] = 'Cuairtich a’ dol deiseil';
$a->strings['Rotate CCW (left)'] = 'Cuairtich a’ dol tuathail';
$a->strings['This is you'] = 'Seo thusa';
$a->strings['Comment'] = 'Beachd';
$a->strings['Select'] = 'Tagh';
$a->strings['Delete'] = 'Sguab às';
$a->strings['Like'] = '’S toigh leam seo';
$a->strings['I like this (toggle)'] = '’S toigh leam seo (toglaich)';
$a->strings['Dislike'] = 'Cha toigh leam seo';
$a->strings['I don\'t like this (toggle)'] = 'Cha toigh leam seo (toglaich)';
$a->strings['Map'] = 'Mapa';
$a->strings['View Album'] = 'Seall an t-albam';
$a->strings['Bad Request.'] = 'Droch-iarrtas.';
$a->strings['Contact not found.'] = 'Cha deach an neach-aithne a lorg.';
$a->strings['[Friendica System Notify]'] = '[Brath siostam Friendica]';
$a->strings['User deleted their account'] = 'Sguab an cleachdaiche às an cunntas aca';
$a->strings['On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'] = 'Sguab cleachdaiche às an cunntas aca air an nòd Friendica agad. Dèan cinnteach gun dèid an dàta aca a thoirt air falbh o na lethbhreacan-glèidhidh.';
$a->strings['The user id is %d'] = '’S e %d ID a’ chleachdaiche';
$a->strings['Remove My Account'] = 'Thoir air falbh an cunntas agam';
$a->strings['This will completely remove your account. Once this has been done it is not recoverable.'] = 'Bheir seo air falbh an cunntas agad gu tur. Nuair a bhios sin air a thachairt, cha ghabh aiseag.';
$a->strings['Please enter your password for verification:'] = 'Cuir a-steach am facal-faire agad airson a dhearbhadh:';
$a->strings['Resubscribing to OStatus contacts'] = 'A’ fo-sgrìobhadh a-rithist air luchd-aithne OStatus';
$a->strings['Error'] = [
	0 => 'Mearachd',
	1 => 'Mearachdan',
	2 => 'Mearachdan',
	3 => 'Mearachdan',
];
$a->strings['Failed to connect with email account using the settings provided.'] = 'Cha deach leinn ceangal a dhèanamh leis a’ chunntas puist-d a’ cleachdadh nan roghainnean a chaidh a thoirt seachad.';
$a->strings['Connected Apps'] = 'Aplacaidean ceangailte';
$a->strings['Name'] = 'Ainm';
$a->strings['Home Page'] = 'Duilleag-dhachaigh';
$a->strings['Created'] = 'Air a chruthachadh';
$a->strings['Remove authorization'] = 'Thoir an t-ùghdarrachadh air falbh';
$a->strings['Save Settings'] = 'Sàbhail na roghainnean';
$a->strings['Addon Settings'] = 'Roghainnean nan tuilleadan';
$a->strings['No Addon settings configured'] = 'Cha deach roghainnean tuilleadain a rèiteachadh';
$a->strings['Additional Features'] = 'Gleusan a bharrachd';
$a->strings['Diaspora (Socialhome, Hubzilla)'] = 'Diaspora (Socialhome, Hubzilla)';
$a->strings['enabled'] = 'an comas';
$a->strings['disabled'] = 'à comas';
$a->strings['Built-in support for %s connectivity is %s'] = 'Tha an taic do chomas-ceangail le %s a thig ’na bhroinn %s';
$a->strings['OStatus (GNU Social)'] = 'OStatus (GNU Social)';
$a->strings['Email access is disabled on this site.'] = 'Tha an t-inntrigeadh le post-d à comas dhan làrach seo.';
$a->strings['None'] = 'Chan eil gin';
$a->strings['Social Networks'] = 'Lìonraidhean sòisealta';
$a->strings['General Social Media Settings'] = 'Roghainnean coitcheann nam meadhanan sòisealta';
$a->strings['Followed content scope'] = 'Farsaingeachd na susbainte air a leanas tu';
$a->strings['By default, conversations in which your follows participated but didn\'t start will be shown in your timeline. You can turn this behavior off, or expand it to the conversations in which your follows liked a post.'] = 'Nochdaidh na còmhraidhean sa ghabh an fheadhainn air a leanas tu pàirt ach nach do thòisich iad fhèin air an loidhne-ama agad a ghnàth. ’S urrainn dhut seo a chur dheth no a leudachadh ach an nochd na còmhraidhean far an toigh leis an fheadhainn air a leanas tu post.';
$a->strings['Only conversations my follows started'] = 'Na còmhraidhean a thòisich cuideigin air a leanas mi a-mhàin';
$a->strings['Conversations my follows started or commented on (default)'] = 'Na còmhraidhean a thòisich cuideigin air a leanas mi no a chuir iad beachd riutha (bun-roghainn)';
$a->strings['Any conversation my follows interacted with, including likes'] = 'Còmhradh sam bith air an robh cuideigin air a leanas mi an sàs, a’ gabhail a-staigh nas toigh leotha';
$a->strings['Enable Content Warning'] = 'Cuir rabhadh susbainte an comas';
$a->strings['Users on networks like Mastodon or Pleroma are able to set a content warning field which collapse their post by default. This enables the automatic collapsing instead of setting the content warning as the post title. Doesn\'t affect any other content filtering you eventually set up.'] = '’S urrainn dhan fheadhainn air lìonraidhean mar Mastodon no Pleroma raon rabhadh susbainte a shuidheachadh a cho-theannaicheas am post aca a ghnàth. Cuiridh seo an co-theannachadh fèin-obrachail an comas seach a bhith a’ suidheachadh an rabhadh susbainte mar thiotal a’ phuist. Cha doir seo buaidh air criathradh susbainte sam bith eile a shuidhicheas tu.';
$a->strings['Enable intelligent shortening'] = 'Cuir an giorrachadh tapaidh an comas';
$a->strings['Normally the system tries to find the best link to add to shortened posts. If disabled, every shortened post will always point to the original friendica post.'] = 'Mar as àbhaist, feuchaidh an siostam gun dèid an ceangal as fheàrr a lorg gus a chur ri postaichean giorraichte. Ma tha seo à comas, tomhaidh gach post giorraichte ris a’ phost tùsail air friendica an-còmhnaidh.';
$a->strings['Enable simple text shortening'] = 'Cuir an comas giorrachadh teacsa sìmplidh';
$a->strings['Normally the system shortens posts at the next line feed. If this option is enabled then the system will shorten the text at the maximum character limit.'] = 'Mar as àbhaist, giorraichidh an siostam na postaichean aig an ath earrann. Ma tha an roghainn seo an comas, giorraichidh an siostam an teacsa aig crìoch nan caractaran ceadaichte.';
$a->strings['Attach the link title'] = 'Cuir tiotal a’ cheangail ris';
$a->strings['When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.'] = 'Nuair a bhios seo an gnìomh, thèid tiotal a’ cheangail a chur ris mar tiotal air postaichean gu Diaspora. Tha seo as fheumaile dhan luchd-aithne “remote-self” a cho-roinneas susbaint inbhir.';
$a->strings['Your legacy ActivityPub/GNU Social account'] = 'An cunntas ActivityPub/GNU Social dìleabach agad';
$a->strings['If you enter your old account name from an ActivityPub based system or your GNU Social/Statusnet account name here (in the format user@domain.tld), your contacts will be added automatically. The field will be emptied when done.'] = 'Ma chuireas tu ainm seann-chunntais ris o shiostam stèidhichte air ActivityPub no ainm do chunntais GNU Social/Statusnet an-seo (san fhòrmat cleachdaiche@àrainn.tld), thèid an luchd-aithne agad a chur ris gu fèin-obrachail. Thèid an raon fhalamhachadh nuair a bhios sin deiseil.';
$a->strings['Repair OStatus subscriptions'] = 'Càraich fo-sgrìobhaidhean OStatus';
$a->strings['Email/Mailbox Setup'] = 'Suidheachadh a’ phuist-d/a’ bhogsa-phuist';
$a->strings['If you wish to communicate with email contacts using this service (optional), please specify how to connect to your mailbox.'] = 'Ma tha thu airson an t-seirbheis seo a chleachdadh airson conaltradh le luchd-aithne air a’ post-d (gu roghainneil), sònraich an dòigh air a nì thu ceangal leis a’ bhogsa-phuist agad.';
$a->strings['Last successful email check:'] = 'An turas mu dheireadh a chaidh leinn sùil a thoirt air a’ phost-d:';
$a->strings['IMAP server name:'] = 'Ainm frithealaiche IMAP:';
$a->strings['IMAP port:'] = 'Port IMAP:';
$a->strings['Security:'] = 'Tèarainteachd:';
$a->strings['Email login name:'] = 'Ainm clàradh a-steach a’ phuist-d:';
$a->strings['Email password:'] = 'Facal-faire a’ phuist-d:';
$a->strings['Reply-to address:'] = 'An seòladh Freagairt-gu:';
$a->strings['Send public posts to all email contacts:'] = 'Cuir postaichean poblach dhan a h-uile neach-aithne puist-d:';
$a->strings['Action after import:'] = 'Gnìomh às dèid an ion-phortaidh:';
$a->strings['Mark as seen'] = 'Cuir comharra gun deach fhaicinn';
$a->strings['Move to folder'] = 'Gluais gu pasgan';
$a->strings['Move to folder:'] = 'Gluais gu pasgan:';
$a->strings['No suggestions available. If this is a new site, please try again in 24 hours.'] = 'Chan eil moladh sam bith ann. Mas e làrach ùr a th’ ann, feuch ris a-rithist an ceann 24 uair a thìde.';
$a->strings['Friend Suggestions'] = 'Molaidhean charaidean';
$a->strings['photo'] = 'dealbh';
$a->strings['status'] = 'staid';
$a->strings['%1$s tagged %2$s\'s %3$s with %4$s'] = 'Chuir %1$s taga %4$s ri %3$s aig %2$s';
$a->strings['Remove Item Tag'] = 'Thoir air falbh taga an nì';
$a->strings['Select a tag to remove: '] = 'Tagh taga gus a thoirt air falbh: ';
$a->strings['Remove'] = 'Thoir air falbh';
$a->strings['User imports on closed servers can only be done by an administrator.'] = 'Chan fhaod ach rianairean cleachdaichean ion-phortadh gu frithealaichean dùinte.';
$a->strings['This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'] = 'Chlàradh na tha ceadaichte de chunntasan ùra air an làrach seo an-diugh. Feuch ris a-rithist a-màireach.';
$a->strings['Import'] = 'Ion-phortaich';
$a->strings['Move account'] = 'Imrich an cunntas';
$a->strings['You can import an account from another Friendica server.'] = '’S urrainn dhut cunntas ion-phortadh o fhrithealaiche Friendica eile.';
$a->strings['You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.'] = 'Feumaidh tu an cunntas agad às-phortadh on t-seann-fhrithealaiche ’s a luchdadh suas an-seo. Ath-chruthaichidh sinn an seann-chunntas agad an-seo leis an luchd-aithne gu lèir agad. Feuchaidh sinn cuideachd gun leig sinn fios dha do charaidean gun do dh’imrich thu an-seo.';
$a->strings['This feature is experimental. We can\'t import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora'] = 'Chan e ach gleus deuchainneil a tha seo. Chan urrain dhuinn luchd-aithne ion-phortadh on lìonra OStatus (GNU Social/Statusnet) no o Dhiaspora';
$a->strings['Account file'] = 'Faidhle a’ chunntais';
$a->strings['To export your account, go to "Settings->Export your personal data" and select "Export account"'] = 'Airson an cunntas agad às-phortadh, tadhail air “Roghainnean” -> “Às-phortaich an dàta pearsanta agad” agus tagh “Às-phortaich an cunntas”';
$a->strings['You aren\'t following this contact.'] = 'Chan eil thu a’ leantainn air an neach-aithne seo.';
$a->strings['Unfollowing is currently not supported by your network.'] = 'Cha chuir an lìonra agad taic ri sgur de leantainn air an àm seo.';
$a->strings['Disconnect/Unfollow'] = 'Dì-cheangail/Na lean tuilleadh';
$a->strings['Contact was successfully unfollowed'] = 'Chan eil thu a’ leantainn air an neach-aithne tuilleadh';
$a->strings['Unable to unfollow this contact, please contact your administrator'] = 'Cha deach leinn an neach-aithne a thoirt air falbh on fheadhainn air a leanas tu, cuir fios gun rianaire agad';
$a->strings['Invalid request.'] = 'Iarrtas mì-dhligheach.';
$a->strings['Sorry, maybe your upload is bigger than the PHP configuration allows'] = 'Tha sinn duilich a dh’fhaoidte gu bheil an luchdadh suas agad nas motha na tha ceadaichte leis an rèiteachadh PHP';
$a->strings['Or - did you try to upload an empty file?'] = 'Air neo – an do dh’fheuch thu ri faidhle falamh a luchdadh suas?';
$a->strings['File exceeds size limit of %s'] = 'Tha am faidhle nas motha na tha ceadaichte dhe %s';
$a->strings['File upload failed.'] = 'Dh’fhàillig luchdadh suas an fhaidhle.';
$a->strings['Wall Photos'] = 'Dealbhan balla';
$a->strings['Number of daily wall messages for %s exceeded. Message failed.'] = 'Chaidh thu thairis air àireamh nan teachdaireachdan-balla làitheil dha %s. Dh’fhàillig leis an teachdaireachd.';
$a->strings['Unable to check your home location.'] = 'Cha b’ urrainn dhuinn sùil a thoir air ionad do dhachaigh.';
$a->strings['No recipient.'] = 'Gun fhaightear.';
$a->strings['If you wish for %s to respond, please check that the privacy settings on your site allow private mail from unknown senders.'] = 'Nam bu mhiann leat gum freagair %s, dearbh gun ceadaich roghainnean prìobhaideachd na làraich agad puist-d phrìobhaideach o sheòladairean nach aithne dhut.';
$a->strings['No system theme config value set.'] = 'Cha deach luach a shuidheachadh do rèiteachadh ùrlar an t-siostaim.';
$a->strings['Apologies but the website is unavailable at the moment.'] = 'Tha sinn duilich ach chan eil an làrach-lìn ri fhaighinn an-dràsta.';
$a->strings['Delete this item?'] = 'A bheil thu airson an nì seo a sguabadh às?';
$a->strings['Block this author? They won\'t be able to follow you nor see your public posts, and you won\'t be able to see their posts and their notifications.'] = 'A bheil thu airson an t-ùghdar seo a bhacadh? Chan urrainn dhaibh leantainn ort no na postaichean poblach agad fhaicinn tuilleadh agus chan fhaic thu fhèin na postaichean no na brathan uapa.';
$a->strings['Method not allowed for this module. Allowed method(s): %s'] = 'Chan eil am modh ceadaichte dhan mhòideal seo. Modh(an) ceadaichte: %s';
$a->strings['Page not found.'] = 'Cha deach an duilleag a lorg.';
$a->strings['You must be logged in to use addons. '] = 'Feumaidh tu clàradh a-steach mus urrainn dhut tuilleadain a chleachdadh. ';
$a->strings['The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.'] = 'Cha robh tòcan tèarainteachd an fhoirm mar bu chòir. Tha sinn an dùil gun do thachair sin air sgàth ’s gun robh am foirm fosgailte do fhada (>3 uairean a thìde) mus deach a chur a-null.';
$a->strings['All contacts'] = 'A h-uile neach-aithne';
$a->strings['Followers'] = 'Luchd-leantainn';
$a->strings['Following'] = 'A’ leantainn';
$a->strings['Mutual friends'] = 'Caraidean an cumantas';
$a->strings['Common'] = 'Cumanta';
$a->strings['Addon not found'] = 'Cha deach an tuilleadan a lorg';
$a->strings['Addon already enabled'] = 'Tha an tuilleadan an comas mu thràth';
$a->strings['Addon already disabled'] = 'Tha an tuilleadan à comas mu thràth';
$a->strings['Could not find any unarchived contact entry for this URL (%s)'] = 'Cha do lorg sinn neach-aithne nach eil san tasg-lann dhan URL seo (%s)';
$a->strings['The contact entries have been archived'] = 'Chaidh an luchd-aithne a chur san tasg-lann';
$a->strings['Could not find any contact entry for this URL (%s)'] = 'Cha do lorg sinn neach-aithne dhan URL seo (%s)';
$a->strings['The contact has been blocked from the node'] = 'Chaidh an neach-aithne a bhacadh on nòd';
$a->strings['uri-id is empty for contact %s.'] = 'Tha uri-id falamh dhan neach-aithne %s.';
$a->strings['Wrong duplicate found for uri-id %d in %d (url: %s != %s).'] = 'Lorg sinn an dùblachadh ceàrr dha uri-id %d am broinn %d (url: %s != %s).';
$a->strings['Wrong duplicate found for uri-id %d in %d (nurl: %s != %s).'] = 'Lorg sinn an dùblachadh ceàrr dha uri-id %d am broinn %d (nurl: %s != %s).';
$a->strings['Deletion of id %d failed'] = 'Cha deach leinn id %d a sguabadh às';
$a->strings['Deletion of id %d was successful'] = 'Chaidh id %d a sguabadh às';
$a->strings['Updating "%s" in "%s" from %d to %d'] = 'Ag ùrachadh “%s” am broinn “%s” o %d gu %d';
$a->strings[' - found'] = ' – air a lorg';
$a->strings[' - failed'] = ' – air fàilligeadh';
$a->strings[' - success'] = ' – chaidh leis';
$a->strings[' - deleted'] = ' – air a sguabadh às';
$a->strings[' - done'] = ' – deiseil';
$a->strings['The avatar cache needs to be enabled to use this command.'] = 'Feumaidh tasgadan nan avatar a bhith an comas mus urrainn dhut an àithne seo a chleachdadh.';
$a->strings['no resource in photo %s'] = 'chan eil goireas san dealbh %s';
$a->strings['no photo with id %s'] = 'chan eil dealbh leis an id %s ann';
$a->strings['no image data for photo with id %s'] = 'chan eil dàta deilbh dhan dealbh leis an id %s ann';
$a->strings['invalid image for id %s'] = 'dealbh mì-dhligheach dhan id %s';
$a->strings['Quit on invalid photo %s'] = 'Fàg an-seo ma tha dealbh %s mì-dhligheach ann';
$a->strings['Post update version number has been set to %s.'] = 'Chaidh àireamh tionndadh an ùrachaidh aig a’ phost a shuidheachadh air %s.';
$a->strings['Check for pending update actions.'] = 'A’ sgrùdadh air gnìomhan ùrachaidh rin dèiligeadh.';
$a->strings['Done.'] = 'Deiseil.';
$a->strings['Execute pending post updates.'] = 'A’ gnìomhachadh nan ùrachaidhean air postaichean rin dèiligeadh.';
$a->strings['All pending post updates are done.'] = 'Tha na h-ùrachaidhean air postaichean rin dèiligeadh deiseil.';
$a->strings['Enter user nickname: '] = 'Cuir a-steach far-ainm a’ chleachdaiche: ';
$a->strings['Enter new password: '] = 'Cuir a-steach am facal-faire ùr: ';
$a->strings['Password update failed. Please try again.'] = 'Dh’fhàillig ùrachadh an fhacail-fhaire. Feuch ris a-rithist.';
$a->strings['Password changed.'] = 'Chaidh am facal-faire atharrachadh.';
$a->strings['Enter user name: '] = 'Cuir a-steach ainm-cleachdaiche: ';
$a->strings['Enter user email address: '] = 'Cuir a-steach seòladh puist-d a’ chleachdaiche: ';
$a->strings['Enter a language (optional): '] = 'Cuir a-steach cànan (roghainneil): ';
$a->strings['User is not pending.'] = 'Chan eil an cleachdaiche ri dhèiligeadh.';
$a->strings['User has already been marked for deletion.'] = 'Chaidh comharra a chur mu thràth gun tèid an cleachdaiche a sguabadh às.';
$a->strings['Deletion aborted.'] = 'Chaidh sgur dhen sguabadh às.';
$a->strings['Enter category: '] = 'Cuir a-steach roinn-seòrsa: ';
$a->strings['Enter key: '] = 'Cuir a-steach iuchair: ';
$a->strings['Enter value: '] = 'Cuir a-steach luach: ';
$a->strings['newer'] = 'nas ùire';
$a->strings['older'] = 'nas sine';
$a->strings['Frequently'] = 'Gu tric';
$a->strings['Hourly'] = 'Gach uair a thìde';
$a->strings['Twice daily'] = 'Dà thuras gach latha';
$a->strings['Daily'] = 'Gach latha';
$a->strings['Weekly'] = 'Gach seachdain';
$a->strings['Monthly'] = 'Gach mìos';
$a->strings['DFRN'] = 'DFRN';
$a->strings['OStatus'] = 'OStatus';
$a->strings['RSS/Atom'] = 'RSS/Atom';
$a->strings['Email'] = 'Post-d';
$a->strings['Diaspora'] = 'Diaspora';
$a->strings['Zot!'] = 'Zot!';
$a->strings['LinkedIn'] = 'LinkedIn';
$a->strings['XMPP/IM'] = 'XMPP/IM';
$a->strings['MySpace'] = 'MySpace';
$a->strings['Google+'] = 'Google+';
$a->strings['pump.io'] = 'pump.io';
$a->strings['Twitter'] = 'Twitter';
$a->strings['Discourse'] = 'Discourse';
$a->strings['Diaspora Connector'] = 'Ceangladair diaspora';
$a->strings['GNU Social Connector'] = 'Ceangladair GNU Social';
$a->strings['ActivityPub'] = 'ActivityPub';
$a->strings['pnut'] = 'pnut';
$a->strings['%s (via %s)'] = '%s (slighe %s)';
$a->strings['%s likes this.'] = '’S toigh le %s seo.';
$a->strings['%s doesn\'t like this.'] = 'Cha toigh le %s seo.';
$a->strings['%s attends.'] = 'Bidh %s an làthair.';
$a->strings['%s doesn\'t attend.'] = 'Cha bhi %s an làthair.';
$a->strings['%s attends maybe.'] = '’S dòcha gum bi %s an làthair.';
$a->strings['%s reshared this.'] = 'Co-roinn %s seo.';
$a->strings['and'] = 'agus';
$a->strings['and %d other people'] = 'agus %d eile';
$a->strings['<span  %1$s>%2$d people</span> like this'] = '’S toigh le <span  %1$s>%2$d</span> seo';
$a->strings['%s like this.'] = '’S toigh le %s seo.';
$a->strings['<span  %1$s>%2$d people</span> don\'t like this'] = 'Cha toigh le <span  %1$s>%2$d</span> seo';
$a->strings['%s don\'t like this.'] = 'Cha toigh le %s seo.';
$a->strings['<span  %1$s>%2$d people</span> attend'] = 'Bidh <span  %1$s>%2$d</span> an làthair';
$a->strings['%s attend.'] = 'Bidh %s an làthair.';
$a->strings['<span  %1$s>%2$d people</span> don\'t attend'] = 'Cha bhi <span  %1$s>%2$d</span> an làthair';
$a->strings['%s don\'t attend.'] = 'Cha bhi %s an làthair.';
$a->strings['<span  %1$s>%2$d people</span> attend maybe'] = '’S dòcha gum bi <span  %1$s>%2$d</span> an làthair';
$a->strings['%s attend maybe.'] = '’S dòcha gum bi %s an làthair.';
$a->strings['<span  %1$s>%2$d people</span> reshared this'] = 'Cho-roinn <span  %1$s>%2$d</span> seo';
$a->strings['Visible to <strong>everybody</strong>'] = 'Chì <strong>a h-uile duine</strong> e';
$a->strings['Please enter a image/video/audio/webpage URL:'] = 'Cuir a-steach URL deilbh/video/fuaime/làraich-lìn:';
$a->strings['Tag term:'] = 'Teirm tagaidh:';
$a->strings['Save to Folder:'] = 'Sàbhail gu pasgan:';
$a->strings['Where are you right now?'] = 'Càit a bheil thu an-dràsta?';
$a->strings['Delete item(s)?'] = 'An sguab thu seo às?';
$a->strings['Created at'] = 'Air a chruthachadh';
$a->strings['New Post'] = 'Post ùr';
$a->strings['Share'] = 'Co-roinn';
$a->strings['Image'] = 'Dealbh';
$a->strings['Video'] = 'Video';
$a->strings['Scheduled at'] = 'Air an sgeideal';
$a->strings['Pinned item'] = 'Nì prìnichte';
$a->strings['View %s\'s profile @ %s'] = 'Seall a’ phròifil aig %s @ %s';
$a->strings['Categories:'] = 'Roinnean-seòrsa:';
$a->strings['Filed under:'] = 'Air a chlàradh fo:';
$a->strings['%s from %s'] = '%s o %s';
$a->strings['View in context'] = 'Seall le co-theacsa';
$a->strings['remove'] = 'thoir air falbh';
$a->strings['Delete Selected Items'] = 'Sguab às na nithean a thagh thu';
$a->strings['You had been addressed (%s).'] = 'Chaidh d’ ainmeachadh (%s).';
$a->strings['You are following %s.'] = 'Tha thu a’ leantainn air %s.';
$a->strings['You subscribed to one or more tags in this post.'] = 'Dh’fho-sgrìobh thu air taga no dhà sa phost seo.';
$a->strings['Reshared'] = '’Ga cho-roinneadh';
$a->strings['Reshared by %s <%s>'] = '’Ga cho-roinneadh le  %s <%s>';
$a->strings['%s is participating in this thread.'] = 'Tha %s a’ gabhail pàirt san t-snàithlean seo.';
$a->strings['Stored for general reasons'] = 'Chaidh a stòradh air adhbharan coitcheann';
$a->strings['Global post'] = 'Post co-naisgte';
$a->strings['Sent via an relay server'] = 'Chaidh a chur slighe frithealaiche ath-sheachadain';
$a->strings['Sent via the relay server %s <%s>'] = 'Chaidh a chur slighe frithealaiche ath-sheachadain %s <%s>';
$a->strings['Fetched'] = 'Air fhaighinn';
$a->strings['Fetched because of %s <%s>'] = 'Air fhaighinn ri linn %s <%s>';
$a->strings['Stored because of a child post to complete this thread.'] = 'Chaidh a stòradh air sàilleibh post-cloinne airson an snàithlean iomlain fhaighinn.';
$a->strings['Local delivery'] = 'Lìbhrigeadh ionadail';
$a->strings['Stored because of your activity (like, comment, star, ...)'] = 'Chaidh a stòradh air sàilleibh do ghnìomhachd (’s toigh, beachd, rionnag, …)';
$a->strings['Distributed'] = 'Sgaoilte';
$a->strings['Pushed to us'] = 'Air a phutadh thugainne';
$a->strings['General Features'] = 'Gleusan coitcheann';
$a->strings['Photo Location'] = 'Ionad an deilbh';
$a->strings['Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'] = 'Thèid meata-dàta nan dealbhan a rùsgadh air falbh. Togaidh seo an t-ionad (ma tha gin ann) mus dèid am meata-dàta a rùsgadh is thèid a cheangal ri mapa.';
$a->strings['Trending Tags'] = 'Tagaichean a’ treandadh';
$a->strings['Show a community page widget with a list of the most popular tags in recent public posts.'] = 'Seall widget duilleag coimhearsnachd le liosta nan tagaichean as fhèillmhoire sna postaichean poblach as ùire.';
$a->strings['Post Composition Features'] = 'Gleusan sgrìobhadh puist';
$a->strings['Auto-mention Forums'] = 'Thoir iomradh air fòram gu fèin-obrachail';
$a->strings['Add/remove mention when a forum page is selected/deselected in ACL window.'] = 'Cuir ris/thoir air falbh an t-iomradh nuair a thèid duilleag fòraim a thaghadh no dì-thaghadh san uinneag ACL.';
$a->strings['Explicit Mentions'] = 'Iomraidhean soilleir';
$a->strings['Add explicit mentions to comment box for manual control over who gets mentioned in replies.'] = 'Cuir iomraidhean soilleir ri bogsa a’ bheachd airson smachd a làimh air cò air a thèid iomradh a dhèanamh ann am freagairtean.';
$a->strings['Add an abstract from ActivityPub content warnings'] = 'Cuir geàrr-chunntas ris o rabhaidhean susbainte ActivityPub';
$a->strings['Add an abstract when commenting on ActivityPub posts with a content warning. Abstracts are displayed as content warning on systems like Mastodon or Pleroma.'] = 'Cuir geàrr-chunntas ris nuair a bhios tu a’ beachdachadh air postaichean ActivityPub le rabhadh susbainte riutha. Thèid geàrr-chunntasan a shealltainn ’nan rabhaidhean susbainte air siostaman mar Mastodon no Pleroma.';
$a->strings['Post/Comment Tools'] = 'Innealan postaidh/beachdachaidh';
$a->strings['Post Categories'] = 'Roinnean-seòrsa nam post';
$a->strings['Add categories to your posts'] = 'Cuir roinnean-seòrsa ris na postaichean agad';
$a->strings['Advanced Profile Settings'] = 'Roghainnean adhartach na pròifile';
$a->strings['List Forums'] = 'Liosta nam fòraman';
$a->strings['Show visitors public community forums at the Advanced Profile Page'] = 'Seall fòraman poblach na coimhearsnachd dhan fheadhainn a thadhlas air duilleag adhartach na pròifil';
$a->strings['Tag Cloud'] = 'Neul nan tagaichean';
$a->strings['Provide a personal tag cloud on your profile page'] = 'Solair neul thagaichean pearsanta air duilleag do phròifile';
$a->strings['Display Membership Date'] = 'Seall ceann-là na ballrachd';
$a->strings['Display membership date in profile'] = 'Seall ceann-là na ballrachd sa phròifil';
$a->strings['Forums'] = 'Fòraman';
$a->strings['External link to forum'] = 'Ceangal cèin dhan fhòram';
$a->strings['show less'] = 'seall nas lugha dheth';
$a->strings['show more'] = 'seall barrachd dheth';
$a->strings['event'] = 'tachartas';
$a->strings['Follow Thread'] = 'Lean air an t-snàithlean';
$a->strings['View Status'] = 'Seall an staid';
$a->strings['View Profile'] = 'Seall a’ phròifil';
$a->strings['View Photos'] = 'Seall na dealbhan';
$a->strings['Network Posts'] = 'Postaichean lìonraidh';
$a->strings['View Contact'] = 'Seall an neach-aithne';
$a->strings['Send PM'] = 'Cuir TPh';
$a->strings['Block'] = 'Bac';
$a->strings['Ignore'] = 'Leig seachad';
$a->strings['Languages'] = 'Cànanan';
$a->strings['Nothing new here'] = 'Chan eil dad ùr an-seo';
$a->strings['Go back'] = 'Air ais';
$a->strings['Clear notifications'] = 'Falamhaich na brathan';
$a->strings['@name, !forum, #tags, content'] = '@ainm, !fòram, #tagaichean, susbaint';
$a->strings['Logout'] = 'Clàraich a-mach';
$a->strings['End this session'] = 'Cuir crìoch air an t-seisean seo';
$a->strings['Login'] = 'Clàraich a-steach';
$a->strings['Sign in'] = 'Clàraich a-steach';
$a->strings['Status'] = 'Staid';
$a->strings['Your posts and conversations'] = 'Na postaichean ’s còmhraidhean agad';
$a->strings['Profile'] = 'Pròifil';
$a->strings['Your profile page'] = 'Duilleag na pròifil agad';
$a->strings['Your photos'] = 'Na dealbhan agad';
$a->strings['Media'] = 'Meadhanan';
$a->strings['Your postings with media'] = 'Na postaichean agad sa bheil meadhanan';
$a->strings['Your events'] = 'Na tachartasan agad';
$a->strings['Personal notes'] = 'Nòtaichean pearsanta';
$a->strings['Your personal notes'] = 'Na nòtaichean pearsanta agad';
$a->strings['Home'] = 'Dachaigh';
$a->strings['Register'] = 'Clàraich leinn';
$a->strings['Create an account'] = 'Cruthaich cunntas';
$a->strings['Help'] = 'Cobhair';
$a->strings['Help and documentation'] = 'Cobhair is docamaideadh';
$a->strings['Apps'] = 'Aplacaidean';
$a->strings['Addon applications, utilities, games'] = 'Tuilleadain aplacaide, goireis is geama';
$a->strings['Search'] = 'Lorg';
$a->strings['Search site content'] = 'Lorg susbaint san làrach';
$a->strings['Full Text'] = 'Teacsa slàn';
$a->strings['Tags'] = 'Tagaichean';
$a->strings['Contacts'] = 'Luchd-aithne';
$a->strings['Community'] = 'Coimhearsnachd';
$a->strings['Conversations on this and other servers'] = 'Còmhraidhean air an fhrithealaiche seo is frithealaichean eile';
$a->strings['Events and Calendar'] = 'Tachartasan ’s mìosachan';
$a->strings['Directory'] = 'Eòlaire';
$a->strings['People directory'] = 'Eòlaire nan daoine';
$a->strings['Information'] = 'Fiosrachadh';
$a->strings['Information about this friendica instance'] = 'Fiosrachadh mun ionstans Friendica seo';
$a->strings['Terms of Service'] = 'Teirmichean na seirbheise';
$a->strings['Terms of Service of this Friendica instance'] = 'Teirmichean seirbheise an ionstans Friendica seo';
$a->strings['Network'] = 'Lìonra';
$a->strings['Conversations from your friends'] = 'Còmhraidhean nan caraidean agad';
$a->strings['Friend Requests'] = 'Iarrtasan càirdeis';
$a->strings['Notifications'] = 'Brathan';
$a->strings['See all notifications'] = 'Seall gach brath';
$a->strings['Mark all system notifications as seen'] = 'Cuir comharra gun deach gach brath an t-siostaim a leughadh';
$a->strings['Private mail'] = 'Post prìobhaideach';
$a->strings['Inbox'] = 'Am bogsa a-steach';
$a->strings['Outbox'] = 'Am bogsa a-mach';
$a->strings['Accounts'] = 'Cunntasan';
$a->strings['Manage other pages'] = 'Stiùir duilleagan eile';
$a->strings['Settings'] = 'Roghainnean';
$a->strings['Account settings'] = 'Roghainnean a’ chunntais';
$a->strings['Manage/edit friends and contacts'] = 'Stiùir/deasaich caraidean is luchd-aithne';
$a->strings['Admin'] = 'Rianachd';
$a->strings['Site setup and configuration'] = 'Suidheachadh ’s rèiteachadh na làraich';
$a->strings['Navigation'] = 'Seòladaireachd';
$a->strings['Site map'] = 'Mapa na làraich';
$a->strings['Embedding disabled'] = 'Tha an leabachadh à comas';
$a->strings['Embedded content'] = 'Susbaint leabaichte';
$a->strings['first'] = 'dhan toiseach';
$a->strings['prev'] = 'air ais';
$a->strings['next'] = 'air adhart';
$a->strings['last'] = 'dhan deireadh';
$a->strings['Image/photo'] = 'Dealbh';
$a->strings['<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s'] = '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s';
$a->strings['Link to source'] = 'Ceangal dhan tùs';
$a->strings['Click to open/close'] = 'Briog ’ga fhosgladh/dhùnadh';
$a->strings['$1 wrote:'] = 'Sgrìobh $1:';
$a->strings['Encrypted content'] = 'Susbaint chrioptaichte';
$a->strings['Invalid source protocol'] = 'Pròtacal tùis mì-dhligheach';
$a->strings['Invalid link protocol'] = 'Pròtacal ceangail mì-dhligheach';
$a->strings['Loading more entries...'] = 'A’ luchdadh barrachd nithean…';
$a->strings['Follow'] = 'Lean air';
$a->strings['Add New Contact'] = 'Cuir neach-aithne ùr ris';
$a->strings['Enter address or web location'] = 'Cuir a-steach seòladh no ionad-lìn';
$a->strings['Example: bob@example.com, http://example.com/barbara'] = 'Mar eisimpleir: calum@ball-eisimpleir.com, http://ball-eisimpleir.com/oighrig';
$a->strings['Connect'] = 'Ceangail';
$a->strings['%d invitation available'] = [
	0 => 'Tha %d chuireadh ri fhaighinn',
	1 => 'Tha %d chuireadh ri fhaighinn',
	2 => 'Tha %d cuiridhean ri fhaighinn',
	3 => 'Tha %d cuireadh ri fhaighinn',
];
$a->strings['Find People'] = 'Lorg daoine';
$a->strings['Enter name or interest'] = 'Cuir a-steach ainm no ùidh';
$a->strings['Examples: Robert Morgenstein, Fishing'] = 'Mar eisimpleir: Aonghas MacLeòid, iasgach';
$a->strings['Find'] = 'Lorg';
$a->strings['Similar Interests'] = 'Ùidhean an cumantas';
$a->strings['Random Profile'] = 'Pròifil air thuaiream';
$a->strings['Invite Friends'] = 'Thoir cuireadh do charaidean';
$a->strings['Global Directory'] = 'Eòlaire co-naisgte';
$a->strings['Local Directory'] = 'Eòlaire ionadail';
$a->strings['Groups'] = 'Buidhnean';
$a->strings['Everyone'] = 'A h-uile duine';
$a->strings['Relationships'] = 'Dàimhean';
$a->strings['All Contacts'] = 'A h-uile neach-aithne';
$a->strings['Protocols'] = 'Pròtacalan';
$a->strings['All Protocols'] = 'Gach pròtacal';
$a->strings['Saved Folders'] = 'Pasganan air an sàbhaladh';
$a->strings['Everything'] = 'A h-uile càil';
$a->strings['Categories'] = 'Roinnean-seòrsa';
$a->strings['%d contact in common'] = [
	0 => '%d neach-aithne an cumantas',
	1 => '%d luchd-aithne an cumantas',
	2 => '%d luchd-aithne an cumantas',
	3 => '%d luchd-aithne an cumantas',
];
$a->strings['Archives'] = 'Tasg-lannan';
$a->strings['Persons'] = 'Daoine';
$a->strings['Organisations'] = 'Buidhnean';
$a->strings['News'] = 'Naidheachdan';
$a->strings['Account Types'] = 'Seòrsaichean chunntasan';
$a->strings['All'] = 'Na h-uile';
$a->strings['Export'] = 'Às-phortaich';
$a->strings['Export calendar as ical'] = 'Às-phortaich am mìosachan ’na ical';
$a->strings['Export calendar as csv'] = 'Às-phortaich am mìosachan ’na csv';
$a->strings['No contacts'] = 'Chan eil neach-aithne ann';
$a->strings['%d Contact'] = [
	0 => '%d neach-aithne',
	1 => '%d luchd-aithne',
	2 => '%d luchd-aithne',
	3 => '%d luchd-aithne',
];
$a->strings['View Contacts'] = 'Seall an luchd-aithne';
$a->strings['Remove term'] = 'Thoir am briathar air falbh';
$a->strings['Saved Searches'] = 'Lorgan a shàbhail thu';
$a->strings['Trending Tags (last %d hour)'] = [
	0 => 'Tagaichean a’ treandadh (san %d uair a thìde seo chaidh)',
	1 => 'Tagaichean a’ treandadh (san %d uair a thìde seo chaidh)',
	2 => 'Tagaichean a’ treandadh (sna %d uairean a thìde seo chaidh)',
	3 => 'Tagaichean a’ treandadh (san %d uair a thìde seo chaidh)',
];
$a->strings['More Trending Tags'] = 'Barrachd thagaichean a’ treandadh';
$a->strings['XMPP:'] = 'XMPP:';
$a->strings['Matrix:'] = 'Matrix:';
$a->strings['Network:'] = 'Lìonra:';
$a->strings['Unfollow'] = 'Na lean tuilleadh';
$a->strings['Yourself'] = 'Thu fhèin';
$a->strings['Mutuals'] = 'Co-dhàimhean';
$a->strings['Post to Email'] = 'Postaich dhan phost-d';
$a->strings['Public'] = 'Poblach';
$a->strings['This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'] = 'Thèid an t-susbaint seo a shealltainn dhan luchd-leantainn gu lèir agad agus chithear air duilleagan na coimhearsnachd i agus chì a h-uile duine aig a bheil an ceangal seo i.';
$a->strings['Limited/Private'] = 'Cuingichte/Prìobhaideach';
$a->strings['This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.'] = 'Thèid an t-susbaint seo a shealltainn dhan fheadhainn san dàrna bhogsa a-mhàin is chan fhaic an fheadhainn sa bhogsa eile. Cha nochd i gu poblach àite sam bith.';
$a->strings['Show to:'] = 'Seall gu:';
$a->strings['Except to:'] = 'Ach gu:';
$a->strings['Connectors'] = 'Ceangladairean';
$a->strings['The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'] = 'Cha b’ urrainn dhuinn sgrìobadh gu faidhle rèiteachadh an stòir-dhàta “config/local.config.php”. Cleachd an teacsa am broinn seo airson faidhle rèiteachaidh a chruthachadh ann am freumh an fhrithealaiche-lìn agad.';
$a->strings['You may need to import the file "database.sql" manually using phpmyadmin or mysql.'] = 'Dh’fhaoidte gum bi agad ris an fhaidhle “database.sql” ion-phortadh a làimh le phpmyadmin no mysql.';
$a->strings['Please see the file "doc/INSTALL.md".'] = 'Faic am faidhle “doc/INSTALL.md”.';
$a->strings['Could not find a command line version of PHP in the web server PATH.'] = 'Cha do lorg sinn tionndadh loidhne-àithne dhe PHP ann am PATH an fhrithealaiche-lìn.';
$a->strings['If you don\'t have a command line version of PHP installed on your server, you will not be able to run the background processing. See <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>\'Setup the worker\'</a>'] = 'Mur eil tionndadh loidhne-àithne dhe PHP stàlaichte air an fhrithealaiche agad, chan urrainn dhut am pròiseasadh sa chùlaibh a ruith. Faic <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>“Setup the worker”</a>';
$a->strings['PHP executable path'] = 'Slighe sho-ghnìomhaichte PHP';
$a->strings['Enter full path to php executable. You can leave this blank to continue the installation.'] = 'Cuir a-steach an t-slighe shlàn dhan fhaidhle sho-ghnìomhaichte php. ’S urrainn dhut seo fhàgail bàn airson leantainn air adhart leis an stàladh.';
$a->strings['Command line PHP'] = 'Loidhne-àithne PHP';
$a->strings['PHP executable is not the php cli binary (could be cgi-fgci version)'] = 'Chan e am faidhle bìnearaidh cli a tha san fhaidhle sho-ghnìomhaichte PHP (dh’fhaoidte gu bheil an tionndadh cgi-fgci agad)';
$a->strings['Found PHP version: '] = 'Chaidh tionndadh dhe PHP a lorg: ';
$a->strings['PHP cli binary'] = 'Faidhle bìnearaidh cli PHP';
$a->strings['The command line version of PHP on your system does not have "register_argc_argv" enabled.'] = 'Chan eil “register_argc_argv” an comas aig an tionndadh loidhne-àithne dhe PHP air an t-siostam agad.';
$a->strings['This is required for message delivery to work.'] = 'Tha seo riatanach ach an obraich lìbhrigeadh nan teachdaireachdan.';
$a->strings['PHP register_argc_argv'] = 'PHP register_argc_argv';
$a->strings['Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'] = 'Mearachd: chan urrainn dhan ghleus “openssl_pkey_new” air an t-siostam seo iuchraichean crioptachaidh a gintinn';
$a->strings['If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".'] = 'Ma tha thu ’ga ruith fo Windows, faic “http://www.php.net/manual/en/openssl.installation.php”.';
$a->strings['Generate encryption keys'] = 'A’ gintinn nan iuchraichean crioptachaidh';
$a->strings['Error: Apache webserver mod-rewrite module is required but not installed.'] = 'Mearachd: Tha mòideal mod-rewrite aig an fhrithealaiche-lìn Apache riatanach ach cha deach a stàladh.';
$a->strings['Apache mod_rewrite module'] = 'Mòideal Apache mod_rewrite';
$a->strings['Error: PDO or MySQLi PHP module required but not installed.'] = 'Mearachd: Tha mòideal PDO no MySQLi aig PHP riatanach ach cha deach gin diubh a stàladh.';
$a->strings['Error: The MySQL driver for PDO is not installed.'] = 'Mearachd: Cha deach an draibhear MySQL airson PDO a stàladh.';
$a->strings['PDO or MySQLi PHP module'] = 'Mòideal PDO no MySQLi aig PHP';
$a->strings['Error, XML PHP module required but not installed.'] = 'Mearachd: Tha am mòideal XML aig PHP riatanach ach cha deach a stàladh.';
$a->strings['XML PHP module'] = 'Mòideal XML aig PHP';
$a->strings['libCurl PHP module'] = 'Mòideal libCurl aig PHP';
$a->strings['Error: libCURL PHP module required but not installed.'] = 'Mearachd: Tha am mòideal libCurl aig PHP riatanach ach cha deach a stàladh.';
$a->strings['GD graphics PHP module'] = 'Mòideal grafaigeachd GD aig PHP';
$a->strings['Error: GD graphics PHP module with JPEG support required but not installed.'] = 'Mearachd: Tha am mòideal grafaigeachd GD aig PHP le taic dha JPEG riatanach ach cha deach a stàladh.';
$a->strings['OpenSSL PHP module'] = 'Mòideal OpenSSL aig PHP';
$a->strings['Error: openssl PHP module required but not installed.'] = 'Mearachd: Tha am mòideal openssl aig PHP riatanach ach cha deach a stàladh.';
$a->strings['mb_string PHP module'] = 'mòideal mb_string aig PHP';
$a->strings['Error: mb_string PHP module required but not installed.'] = 'Mearachd: Tha am mòideal mb_string aig PHP riatanach ach cha deach a stàladh.';
$a->strings['iconv PHP module'] = 'Mòideal iconv aig PHP';
$a->strings['Error: iconv PHP module required but not installed.'] = 'Mearachd: Tha am mòideal iconv aig PHP riatanach ach cha deach a stàladh.';
$a->strings['POSIX PHP module'] = 'Mòideal POSIX aig PHP';
$a->strings['Error: POSIX PHP module required but not installed.'] = 'Mearachd: Tha am mòideal POSIX aig PHP riatanach ach cha deach a stàladh.';
$a->strings['Program execution functions'] = 'Foincseanan gnìomhachadh phrògraman';
$a->strings['Error: Program execution functions (proc_open) required but not enabled.'] = 'Mearachd: Tha foincseanan gnìomhachadh phrògraman (proc_open) riatanach ach cha deach an cur an comas.';
$a->strings['JSON PHP module'] = 'Mòideal JSON aig PHP';
$a->strings['Error: JSON PHP module required but not installed.'] = 'Mearachd: Tha am mòideal JSON aig PHP riatanach ach cha deach a stàladh.';
$a->strings['File Information PHP module'] = 'Mòideal File Information aig PHP';
$a->strings['Error: File Information PHP module required but not installed.'] = 'Mearachd: Tha am mòideal File Information aig PHP aig PHP riatanach ach cha deach a stàladh.';
$a->strings['GNU Multiple Precision PHP module'] = 'Mòideal GNU Multiple Precision aig PHP';
$a->strings['Error: GNU Multiple Precision PHP module required but not installed.'] = 'Mearachd: Tha am mòideal GNU Multiple Precision aig PHP riatanach ach cha deach a stàladh.';
$a->strings['The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server and it is unable to do so.'] = 'Feumaidh an stàlaichear-lìn faidhle a chruthachadh air a bheil “local.config.php” ann am pasgan “config” an fhrithealaiche-lìn agad is chan urrainn dha sin a dhèanamh.';
$a->strings['This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.'] = 'Mar as trice, is roghainn cead as coireach agus ’s dòcha nach fhaod am frithealaiche-lìn faidhlichean a sgrìobhadh sa phasgan agad –  fiù mas urrainn dhut fhèin.';
$a->strings['At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.'] = 'Air deireadh na sgeòil, bheir sinn teacsa thugad ach an sàbhail thu e ann am faidhle air a bheil local.config.php sa phasgan “config” aig Friendica.';
$a->strings['You can alternatively skip this procedure and perform a manual installation. Please see the file "doc/INSTALL.md" for instructions.'] = 'Mar roghainn eile, leig seo dhan dàrna taobh agus dèan stàladh a làimh. Faic am faidhle “doc/INSTALL.md” airson stiùireadh.';
$a->strings['config/local.config.php is writable'] = 'Gabhaidh sgrìobhadh san fhaidhle config/local.config.php';
$a->strings['Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.'] = 'Cleachdaidh Friendica einnsean theamplaidean Smarty3 airson na seallaidhean-lìn aige a reandaradh. Trusaidh Smarty3 na teamplaidean gu PHP airson an reandaradh a dhèanamh nas luaithe.';
$a->strings['In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.'] = 'Airson na teamplaidean sin a stòradh às dèidh an trusaidh, feumaidh inntrigeadh a chùm sgrìobhaidh sa phasgan view/smarty3/ fo phrìomh-phasgan Friendica a bhith aig an fhrithealaiche-lìn.';
$a->strings['Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.'] = 'Dèan cinnteach gu bheil inntrigeadh a chùm sgrìobhaidh dhan phasgan seo aig a’ chleachdaiche leis a bheil am frithealaiche-lìn ’ga ruith (m.e. www-data).';
$a->strings['Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files (.tpl) that it contains.'] = 'An aire: a chùm tèarainteachd, bu chòir dhut inntrigeadh a chùm sgrìobhaidh a thoirt dhan fhrithealaiche-lìn airson a’ phasgain view/smarty3/ a-mhàin is chan ann dha na faidhlichean teamplaide (.tpl) a tha ’na bhroinn.';
$a->strings['view/smarty3 is writable'] = 'Gabhaidh sgrìobhadh sa phasgan view/smarty3';
$a->strings['Url rewrite in .htaccess seems not working. Make sure you copied .htaccess-dist to .htaccess.'] = 'Tha coltas nach eil an Url rewrite ag obair san fhaidhle .htaccess. Dèan cinnteach gun do chruthaich thu lethbhreac dhe .htaccess-dist aig .htaccess.';
$a->strings['In some circumstances (like running inside containers), you can skip this error.'] = 'Ann an cuid a shuidheachaidhean (can ruith am broinn suithich), ’s urrainn dhut a’ mhearachd seo a leigeil seachad.';
$a->strings['Error message from Curl when fetching'] = 'Teachdaireachd mearachd o Curl rè na faighinn';
$a->strings['Url rewrite is working'] = 'Tha Url rewrite ag obair';
$a->strings['The detection of TLS to secure the communication between the browser and the new Friendica server failed.'] = 'Cha deach leinn mothachadh dha TLS a chùm conaltraidh thèarainte eadar am brabhsair ’s am frithealaiche Friendica ùr.';
$a->strings['It is highly encouraged to use Friendica only over a secure connection as sensitive information like passwords will be transmitted.'] = 'Mholamaid nach cleachd thu Friendica ach thar ceangal tèarainte idir on a thèid fiosrachadh dìomhair mar fhaclan-faire a thar-chur.';
$a->strings['Please ensure that the connection to the server is secure.'] = 'Dèan cinnteach gum bi an ceangal dhan fhrithealaiche tèarainte.';
$a->strings['No TLS detected'] = 'Cha do mhothaich sinn dha TLS';
$a->strings['TLS detected'] = 'Mhothaich sinn dha TLS';
$a->strings['ImageMagick PHP extension is not installed'] = 'Cha deach an tuilleadan ImageMagick aig PHP a stàladh';
$a->strings['ImageMagick PHP extension is installed'] = 'Chaidh an tuilleadan ImageMagick aig PHP a stàladh';
$a->strings['ImageMagick supports GIF'] = 'Cuiridh ImageMagick taic ri GIF';
$a->strings['Database already in use.'] = 'Tha an stòr-dàta ’ga chleachdadh mu thràth.';
$a->strings['Could not connect to database.'] = 'Cha b’ urrainn dhuinn ceangal ris an stòr-dàta.';
$a->strings['Monday'] = 'DiLuain';
$a->strings['Tuesday'] = 'DiMàirt';
$a->strings['Wednesday'] = 'DiCiadain';
$a->strings['Thursday'] = 'DiarDaoin';
$a->strings['Friday'] = 'DihAoine';
$a->strings['Saturday'] = 'DiSathairne';
$a->strings['Sunday'] = 'DiDòmhnaich';
$a->strings['January'] = 'Am Faoilleach';
$a->strings['February'] = 'An Gearran';
$a->strings['March'] = 'Am Màrt';
$a->strings['April'] = 'An Giblean';
$a->strings['May'] = 'An Cèitean';
$a->strings['June'] = 'An t-Ògmhios';
$a->strings['July'] = 'An t-Iuchar';
$a->strings['August'] = 'An Lùnastal';
$a->strings['September'] = 'An t-Sultain';
$a->strings['October'] = 'An Dàmhair';
$a->strings['November'] = 'An t-Samhain';
$a->strings['December'] = 'An Dùbhlachd';
$a->strings['Mon'] = 'DiL';
$a->strings['Tue'] = 'DiM';
$a->strings['Wed'] = 'DiC';
$a->strings['Thu'] = 'Dia';
$a->strings['Fri'] = 'Dih';
$a->strings['Sat'] = 'DiS';
$a->strings['Sun'] = 'DiD';
$a->strings['Jan'] = 'Faoi';
$a->strings['Feb'] = 'Gearr';
$a->strings['Mar'] = 'Màrt';
$a->strings['Apr'] = 'Gibl';
$a->strings['Jun'] = 'Ògmh';
$a->strings['Jul'] = 'Iuch';
$a->strings['Aug'] = 'Lùna';
$a->strings['Sep'] = 'Sult';
$a->strings['Oct'] = 'Dàmh';
$a->strings['Nov'] = 'Samh';
$a->strings['Dec'] = 'Dùbh';
$a->strings['Friendica can\'t display this page at the moment, please contact the administrator.'] = 'Chan urrainn dha Friendica an duilleag seo a shealltainn an-dràsta, cuir fios gun rianaire.';
$a->strings['template engine cannot be registered without a name.'] = 'cha ghabh einnsean theamplaidean a chlàradh gun ainm.';
$a->strings['template engine is not registered!'] = 'cha deach an t-einnsean theamplaidean a chlàradh!';
$a->strings['Storage base path'] = 'Bun-slighe an stòrais';
$a->strings['Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'] = 'Am pasgan far an dèid faidhlichean air an luchdadh suas a shàbhaladh. A chùm na tèarainteachd as fheàrr, mholamaid slighe taobh a-muigh craobh phasganan an fhrithealaiche-lìn';
$a->strings['Enter a valid existing folder'] = 'Cuir a-steach pasgan dligheach a tha ann';
$a->strings['Updates from version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'Chan eil taic ri ùrachadh o thionndadh %s. Ùraich gun tionndadh 2021.01 air a char as sine agus fuirich gus am bith an t-iar-ùrachadh deiseil le tionndadh 1383.';
$a->strings['Updates from postupdate version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'Chan eil taic ri iar-ùrachadh o thionndadh %s. Ùraich gun tionndadh 2021.01 air a char as sine agus fuirich gus am bith an t-iar-ùrachadh deiseil le tionndadh 1383.';
$a->strings['%s: executing pre update %d'] = '%s: a’ dèanamh ro-ùrachadh %d';
$a->strings['%s: executing post update %d'] = '%s: a’ dèanamh iar-ùrachadh %d';
$a->strings['Update %s failed. See error logs.'] = 'Dh’fhàillig le ùrachadh %s. Thoir sùil air logaichean nam mearachdan.';
$a->strings['
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can\'t do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.'] = '
				Dh’fhoillsich luchd-leasachaidh Friendica ùrachadh %s o chionn goirid
				ach nuair a dh’fheuch mi ris a stàladh, chaidh rudeigin ceàrr gu dona.
				Feumaidh sinn seo a chàradh a dh’aithghearr ach chan urrainn dhomh sin a dhèanamh ’nam aonar. Cuir fios gu
				neach-leasachaidh Friendica mur urrainn dhut fhèin mo chuideachadh. Dh’fhaoidte nach eil an stòr-dàta agam dligheach.';
$a->strings['The error message is\n[pre]%s[/pre]'] = 'Seo teachdaireachd na mearachd:\n[pre]%s[/pre]';
$a->strings['[Friendica Notify] Database update'] = '[Brath Friendica] Ùrachadh an stòir-dhàta';
$a->strings['
					The friendica database was successfully updated from %s to %s.'] = '
					Chaidh stòr-dàta Friendica ùrachadh o %s gu %s.';
$a->strings['Error decoding account file'] = 'Mearachd le dì-chòdachadh faidhle a’ chunntais';
$a->strings['Error! No version data in file! This is not a Friendica account file?'] = 'Mearachd! Chan eil dàta mun tionndadh san fhaidhle! Nach e faidhle cunntas Friendica a th’ ann?';
$a->strings['User \'%s\' already exists on this server!'] = 'Tha an cleachdaiche “%s” air an fhrithealaiche seo mu thràth!';
$a->strings['User creation error'] = 'Mearachd a’ cruthachadh a’ chleachdaiche';
$a->strings['%d contact not imported'] = [
	0 => 'Tha %d neach-aithne nach deach ion-phortadh',
	1 => 'Tha %d luchd-aithne nach deach ion-phortadh',
	2 => 'Tha %d luchd-aithne nach deach ion-phortadh',
	3 => 'Tha %d luchd-aithne nach deach ion-phortadh',
];
$a->strings['User profile creation error'] = 'Mearachd a’ cruthachadh pròifil a’ chleachdaiche';
$a->strings['Done. You can now login with your username and password'] = 'Deiseil. ’S urrainn dhut clàradh a-steach leis an ainm-chleachdaiche ’s fhacal-fhaire agad a-nis';
$a->strings['The database version had been set to %s.'] = 'Chaidh tionndadh an stòir-dhàta a shuidheachadh air %s.';
$a->strings['The post update is at version %d, it has to be at %d to safely drop the tables.'] = 'Tha an t-iar-ùrachadh air tionndadh %d ach feumaidh e bhith air %d mus gabh na clàran a leigeil às gu sàbhailte.';
$a->strings['No unused tables found.'] = 'Cha deach clàr gun cleachdadh a lorg.';
$a->strings['These tables are not used for friendica and will be deleted when you execute "dbstructure drop -e":'] = 'Chan eil na clàran seo ’gan cleachdadh airson Friendica is thèid an sguabadh às ma ghnìomhaicheas tu “dbstructure drop -e”:';
$a->strings['There are no tables on MyISAM or InnoDB with the Antelope file format.'] = 'Chan eil clàr air MyISAM no InnoDB le fòrmat faidhle Antelope.';
$a->strings['
Error %d occurred during database update:
%s
'] = '
Thachair mearachd %d rè ùrachadh an stòir-dhàta:
%s
';
$a->strings['Errors encountered performing database changes: '] = 'Thachair sinn ri mearachdan nuair a bha sinn ag atharrachadh an stòir-dhàta: ';
$a->strings['Another database update is currently running.'] = 'Tha ùrachadh eile ’ga ruith air an stòr-dàta an-dràsta fhèin.';
$a->strings['%s: Database update'] = '%s: Ùrachadh an stòir-dhàta';
$a->strings['%s: updating %s table.'] = '%s: Ag ùrachadh clàr %s.';
$a->strings['Record not found'] = 'Cha deach an clàr a lorg';
$a->strings['Unprocessable Entity'] = 'Eintiteas nach gabh a phròiseasadh';
$a->strings['Unauthorized'] = 'Gun chead';
$a->strings['Token is not authorized with a valid user or is missing a required scope'] = 'Cha deach an tòcan ùghdarrachadh le cleachdaiche dligheach no tha sgòp riatanach a dhìth';
$a->strings['Internal Server Error'] = 'Mearachd frithealaiche inntearnail';
$a->strings['Legacy module file not found: %s'] = 'Cha deach am faidhle mòideil dìleabach seo a lorg: %s';
$a->strings['UnFollow'] = 'Na lean tuilleadh';
$a->strings['Approve'] = 'Aontaich ris';
$a->strings['Organisation'] = 'Buidheann';
$a->strings['Forum'] = 'Fòram';
$a->strings['Disallowed profile URL.'] = 'URL pròifile mì-dhligheach.';
$a->strings['Blocked domain'] = 'Àrainn bhacte';
$a->strings['Connect URL missing.'] = 'Tha URL a’ cheangail a dhìth.';
$a->strings['The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.'] = 'Cha b’ urrainn dhuinn an neach-aithne a chur ris. Thoir sùil air teisteas an lìonraidh iomchaidh air duilleag nan “Roghainnean” > “Lìonraidhean sòisealta” agad.';
$a->strings['The profile address specified does not provide adequate information.'] = 'Chan eil an seòladh pròifile a shònraich thu a’ solar am fiosrachadh iomchaidh.';
$a->strings['No compatible communication protocols or feeds were discovered.'] = 'Cha do lorg sinn pròtacal conaltraidh no inbhir iomchaidh.';
$a->strings['An author or name was not found.'] = 'Cha deach an t-ùghdar no ainm a lorg.';
$a->strings['No browser URL could be matched to this address.'] = 'Cha b’ urrainn dhuinn URL a’ bhrabhsair a mhaidseadh ris an t-seòladh seo.';
$a->strings['Unable to match @-style Identity Address with a known protocol or email contact.'] = 'Tha b’ urrainn dhuinn an seòladh-aithne san stoidhle @ a mhaidseadh le pròtacal as aithne dhuinn no neach-aithne puist-d.';
$a->strings['Use mailto: in front of address to force email check.'] = 'Cleachd mailto: ron t-seòladh airson sgrùdadh nam post-d a sparradh.';
$a->strings['The profile address specified belongs to a network which has been disabled on this site.'] = 'Tha seòladh na pròifil a shònraich thu a’ buntainn ri lìonra a chaidh a chur à comas air an làrach seo.';
$a->strings['Limited profile. This person will be unable to receive direct/personal notifications from you.'] = 'Pròifil chuingichte. Chan fhaigh an neach seo brathan dìreach/pearsanta uat.';
$a->strings['Unable to retrieve contact information.'] = 'Cha d’ fhuair sinn grèim air fiosrachadh an neach-aithne.';
$a->strings['l F d, Y \@ g:i A \G\M\TP (e)'] = 'l, d F Y \@ g:i a \G\M\TP (e)';
$a->strings['Starts:'] = 'A’ tòiseachadh:';
$a->strings['Finishes:'] = 'Thig e gu crìoch:';
$a->strings['all-day'] = 'fad an latha';
$a->strings['Sept'] = 'Sult';
$a->strings['No events to display'] = 'Chan eil tachartas ri shealltainn ann';
$a->strings['l, F j'] = 'l, j F';
$a->strings['Edit event'] = 'Deasaich an tachartas';
$a->strings['Duplicate event'] = 'Dùblaich an tachartas';
$a->strings['Delete event'] = 'Sguab às an tachartas';
$a->strings['l F d, Y \@ g:i A'] = 'l, d F Y \@ g:i a';
$a->strings['D g:i A'] = 'D g:i a';
$a->strings['g:i A'] = 'g:i a';
$a->strings['Show map'] = 'Seall am mapa';
$a->strings['Hide map'] = 'Falaich am mapa';
$a->strings['%s\'s birthday'] = 'Cò-là breith aig %s';
$a->strings['Happy Birthday %s'] = 'Co-là breith sona dhut, %s';
$a->strings['A deleted group with this name was revived. Existing item permissions <strong>may</strong> apply to this group and any future members. If this is not what you intended, please create another group with a different name.'] = 'Chaidh buidheann a bh’ air a sguabadh às ath-bheòthachadh. <strong>Faodaidh</strong> ceadan a tha ann air nithean a bhith an sàs air a’ bhuidheann seo is air ball ri teachd sam bith. Mur e sin a bha fa-near dhut, cruthaich buidheann eile air a bheil ainm eile.';
$a->strings['Default privacy group for new contacts'] = 'Am buidheann prìobhaideachd bunaiteach do luchd-aithne ùr';
$a->strings['Everybody'] = 'A h-uile duine';
$a->strings['edit'] = 'deasaich';
$a->strings['add'] = 'cuir ris';
$a->strings['Edit group'] = 'Deasaich am buidheann';
$a->strings['Contacts not in any group'] = 'Luchd-aithne gun bhuidheann';
$a->strings['Create a new group'] = 'Cruthaich buidheann ùr';
$a->strings['Group Name: '] = 'Ainm a’ bhuidhinn: ';
$a->strings['Edit groups'] = 'Deasaich buidhnean';
$a->strings['Detected languages in this post:\n%s'] = 'Na cànanan dhan a mhothaich sinn sa phost seo:\n%s';
$a->strings['activity'] = 'gnìomhachd';
$a->strings['comment'] = 'beachd';
$a->strings['post'] = 'post';
$a->strings['Content warning: %s'] = 'Rabhadh susbainte: %s';
$a->strings['bytes'] = 'baidht';
$a->strings['%s (%d%s, %d votes)'] = '%s (%d%s, rinn %d bhòtadh)';
$a->strings['%s (%d votes)'] = '%s (rinn %d bhòtadh)';
$a->strings['%d voters. Poll end: %s'] = 'Rinn %d bhòtadh. Crìoch a’ chunntais-bheachd: %s';
$a->strings['%d voters.'] = 'Rinn %d bhòtadh.';
$a->strings['Poll end: %s'] = 'Crìoch a’ bhunntais-bheachd:%s';
$a->strings['View on separate page'] = 'Seall air duilleag fa leth';
$a->strings['[no subject]'] = '[gun chuspair]';
$a->strings['Edit profile'] = 'Deasaich a’ phròifil';
$a->strings['Change profile photo'] = 'Atharraich dealbh na pròifil';
$a->strings['Homepage:'] = 'Duilleag-dhachaigh:';
$a->strings['About:'] = 'Mu dhèidhinn:';
$a->strings['Atom feed'] = 'Inbhir Atom';
$a->strings['F d'] = 'd F';
$a->strings['[today]'] = '[an-diugh]';
$a->strings['Birthday Reminders'] = 'Cuimhneachain co-là breith';
$a->strings['Birthdays this week:'] = 'Co-làithean breith an t-seachdain seo:';
$a->strings['g A l F d'] = 'g a l d F';
$a->strings['[No description]'] = '[Gun tuairisgeul]';
$a->strings['Event Reminders'] = 'Cuimhneachain air tachartasan';
$a->strings['Upcoming events the next 7 days:'] = 'Tachartasan anns na 7 làithean seo tighinn:';
$a->strings['OpenWebAuth: %1$s welcomes %2$s'] = 'OpenWebAuth: Tha %1$s a’ cur fàilte air %2$s';
$a->strings['Hometown:'] = 'Baile d’ àraich:';
$a->strings['Marital Status:'] = 'Inbhe pòsaidh:';
$a->strings['With:'] = 'Le:';
$a->strings['Since:'] = 'O chionn:';
$a->strings['Sexual Preference:'] = 'Aidmheil cleamhnais:';
$a->strings['Political Views:'] = 'Beachdan poilitigeach:';
$a->strings['Religious Views:'] = 'Beachdan creideamhach:';
$a->strings['Likes:'] = '’S toigh seo le:';
$a->strings['Dislikes:'] = 'Cha toigh seo le:';
$a->strings['Title/Description:'] = 'Tiotal/Tuairisgeul:';
$a->strings['Summary'] = 'Geàrr-chunntas';
$a->strings['Musical interests'] = 'Ùidhean ciùil';
$a->strings['Books, literature'] = 'Leabhraichean ⁊ litreachas';
$a->strings['Television'] = 'Telebhisean';
$a->strings['Film/dance/culture/entertainment'] = 'Film/dannsa/cultar/dibheirsean';
$a->strings['Hobbies/Interests'] = 'Cur-seachadan/ùidhean';
$a->strings['Love/romance'] = 'Gaol/suirghe';
$a->strings['Work/employment'] = 'Obair/fastadh';
$a->strings['School/education'] = 'Sgoil/foghlam';
$a->strings['Contact information and Social Networks'] = 'Fiosrachadh conaltraidh is meadhanan sòisealta';
$a->strings['SERIOUS ERROR: Generation of security keys failed.'] = 'MEARACHD MHÒR: Dh’fhàillig le gintinn nan iuchraichean tèarainteachd.';
$a->strings['Login failed'] = 'Dh’fhàillig leis a’ chlàradh a-steach';
$a->strings['Not enough information to authenticate'] = 'Tha fiosrachadh a dhìth dhan dearbhadh';
$a->strings['Password can\'t be empty'] = 'Chan fhaod am facal-faire a bhith bàn';
$a->strings['Empty passwords are not allowed.'] = 'Chan eil faclan-faire bàna ceadaichte.';
$a->strings['The new password has been exposed in a public data dump, please choose another.'] = 'Chaidh am facal-faire ùr fhoillseachadh ann an dumpadh dàta poblach, tagh fear eile.';
$a->strings['The password length is limited to 72 characters.'] = 'Chan fhaod am facal-faire a bhith nas fhaide na 72 caractar.';
$a->strings['The password can\'t contain accentuated letters, white spaces or colons (:)'] = 'Chan fhaod stràc, àite bàn no còilean (:) a bhith am broinn an fhacail-fhaire.';
$a->strings['Passwords do not match. Password unchanged.'] = 'Chan eil an dà fhacal-faire co-ionnann. Cha deach am facal-faire atharrachadh.';
$a->strings['An invitation is required.'] = 'Tha feum air cuireadh.';
$a->strings['Invitation could not be verified.'] = 'Cha b’ urrainn dhuinn an cuireadh a dhearbhadh.';
$a->strings['Invalid OpenID url'] = 'URL OpenID mì-dhligheach';
$a->strings['We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.'] = 'Thachair sinn ri duilgheadas fhad ’s a bha sinn ’gad clàradh a-steach leis an OpenID a thug thu seachad. Thoir sùil air litreachadh an ID.';
$a->strings['The error message was:'] = 'Seo teachdaireachd na mearachd:';
$a->strings['Please enter the required information.'] = 'Cuir a-steach am fiosrachadh riatanach.';
$a->strings['system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.'] = 'Tha system.username_min_length (%s) agus system.username_max_length (%s) ag às-dùnadh càch a chèile, a’ cur nan luachan an àite càch a chèile.';
$a->strings['Username should be at least %s character.'] = [
	0 => 'Feumaidh co-dhiù %s charactar a bhith am broinn an ainm-chleachdaiche.',
	1 => 'Feumaidh co-dhiù %s charactar a bhith am broinn an ainm-chleachdaiche.',
	2 => 'Feumaidh co-dhiù %s caractaran a bhith am broinn an ainm-chleachdaiche.',
	3 => 'Feumaidh co-dhiù %s caractar a bhith am broinn an ainm-chleachdaiche.',
];
$a->strings['Username should be at most %s character.'] = [
	0 => 'Chan fhaod còrr is %s charactar a bhith am broinn an ainm-chleachdaiche.',
	1 => 'Chan fhaod còrr is %s charactar a bhith am broinn an ainm-chleachdaiche.',
	2 => 'Chan fhaod còrr is %s caractaran a bhith am broinn an ainm-chleachdaiche.',
	3 => 'Chan fhaod còrr is %s caractar a bhith am broinn an ainm-chleachdaiche.',
];
$a->strings['That doesn\'t appear to be your full (First Last) name.'] = 'Chan eil coltas ainm shlàin (ainm ⁊ sloinneadh) air.';
$a->strings['Your email domain is not among those allowed on this site.'] = 'Chan eil àrainn a’ phuist-d agad am measg na feadhna a tha ceadaichte air an làrach seo.';
$a->strings['Not a valid email address.'] = 'Chan e seòladh puist-d dligheach a tha seo.';
$a->strings['The nickname was blocked from registration by the nodes admin.'] = 'Chaidh am far-ainm seo a bhacadh on chlàradh le rianaire an nòid.';
$a->strings['Cannot use that email.'] = 'Chan urrainn dhut am post-d seo a chleachdadh.';
$a->strings['Your nickname can only contain a-z, 0-9 and _.'] = 'Chan fhaod ach a-z, 0-9 ’s _ a bhith am broinn d’ fhar-ainm.';
$a->strings['Nickname is already registered. Please choose another.'] = 'Chaidh am far-ainm seo a chlàradh mu thràth. Nach tagh thu fear eile?';
$a->strings['An error occurred during registration. Please try again.'] = 'Thachair mearachd rè a’ chlàraidh. Feuch ris a-rithist.';
$a->strings['An error occurred creating your default profile. Please try again.'] = 'Thachair mearachd le cruthachadh na pròifile bunaitiche agad. Feuch ris a-rithist.';
$a->strings['An error occurred creating your self contact. Please try again.'] = 'Thachair mearachd le cruthachadh neach-aithne dhiot fhèin. Feuch ris a-rithist.';
$a->strings['Friends'] = 'Caraidean';
$a->strings['An error occurred creating your default contact group. Please try again.'] = 'Thachair mearachd le cruthachadh a’ bhuidhinn conaltraidh bhunaitich agad. Feuch ris a-rithist.';
$a->strings['Profile Photos'] = 'Dealbhan na pròifil';
$a->strings['
		Dear %1$s,
			the administrator of %2$s has set up an account for you.'] = '
		%1$s, a charaid,
			shuidhich rianaire %2$s cunntas dhut.';
$a->strings['
		The login details are as follows:

		Site Location:	%1$s
		Login Name:		%2$s
		Password:		%3$s

		You may change your password from your account "Settings" page after logging
		in.

		Please take a few moments to review the other account settings on that page.

		You may also wish to add some basic information to your default profile
		(on the "Profiles" page) so that other people can easily find you.

		We recommend setting your full name, adding a profile photo,
		adding some profile "keywords" (very useful in making new friends) - and
		perhaps what country you live in; if you do not wish to be more specific
		than that.

		We fully respect your right to privacy, and none of these items are necessary.
		If you are new and do not know anybody here, they may help
		you to make some new and interesting friends.

		If you ever want to delete your account, you can do so at %1$s/removeme

		Thank you and welcome to %4$s.'] = '
		Seo am fiosrachadh clàraidh a-steach:

		Seòladh na làraich:	%1$s
		Ainm clàraidh a-steach:		%2$s
		Facal-faire:		%3$s

		’S urrainn dhut am facal-faire agad atharrachadh air duilleag “Roghainnean” a’ chunntais agad
		às dèidh clàradh a-steach.

		Fhad ’s a bhios tu ris, thoir sùil air roghainnean eile a’ chunntais air an duilleag sin.

		Dh’fhaoidte gum bu mhiann leat fiosrachadh bunasach a chur ris a’ phròifil bhunaiteach agad
		(air duilleag na “Pròifil”) ach am faigh daoine lorg ort gun duilgheadas.

		Mholamaid gun suidhich thu d’ ainm slàn, gun cuir thu dealbh pròifil ris,
		gun cuir thu “facal-luirg” no dhà ris a’ phròifil (glè fheumail airson caraidean ùra fhaighinn) – agus is dòcha
		an dùthaich far a bheil thu mur eil thu airson a bhith nas mionaidiche na sin.

		Tha suim againn dha do phrìobhaideachd is chan eil gin sam bith dhe na nithean sin riatanach idir.
		Ma tha thu ùr an-seo is mur eil thu eòlach air duine sam bith, b’ urrainn dha na nithean seo
		do chuideachadh ach am cuir thu an aithne air caraidean inntinneach ùra.

		Ma tha thu airson an cunntas agad a sguabadh às uair sam bith, ’s urrainn dhut sin a dhèanamh air %1$s/removeme

		Mòran taing agus fàilte gu %4$s.';
$a->strings['Registration details for %s'] = 'Am fiosrachadh clàraidh airson %s';
$a->strings['
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		'] = '
			%1$s, a charaid,
				Mòran taing airson clàradh air %2$s. Tha an cunntas agad a’ feitheamh air aonta on rianaire.

			Seo am fiosrachadh clàraidh a-steach agad:

			Seòladh na làraich:	%3$s
			Ainm clàraidh a-steach:		%4$s
			Facal-faire:		%5$s
		';
$a->strings['Registration at %s'] = 'An clàradh air %s';
$a->strings['
				Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
			'] = '
				%1$s, a charaid,
				Mòran taing airson clàradh air %2$s. Chaidh an cunntas agad a chruthachadh.
			';
$a->strings['
			The login details are as follows:

			Site Location:	%3$s
			Login Name:		%1$s
			Password:		%5$s

			You may change your password from your account "Settings" page after logging
			in.

			Please take a few moments to review the other account settings on that page.

			You may also wish to add some basic information to your default profile
			(on the "Profiles" page) so that other people can easily find you.

			We recommend setting your full name, adding a profile photo,
			adding some profile "keywords" (very useful in making new friends) - and
			perhaps what country you live in; if you do not wish to be more specific
			than that.

			We fully respect your right to privacy, and none of these items are necessary.
			If you are new and do not know anybody here, they may help
			you to make some new and interesting friends.

			If you ever want to delete your account, you can do so at %3$s/removeme

			Thank you and welcome to %2$s.'] = '
			Seo am fiosrachadh clàraidh a-steach:

			Seòladh na làraich:	%3$s
			Ainm clàraidh a-steach:		%1$s
			Facal-faire:		%5$s

			’S urrainn dhut am facal-faire agad atharrachadh air duilleag “Roghainnean” a’ chunntais agad
		\às dèidh clàradh a-steach.

			Fhad ’s a bhios tu ris, thoir sùil air roghainnean eile a’ chunntais air an duilleag sin.

			Dh’fhaoidte gum bu mhiann leat fiosrachadh bunasach a chur ris a’ phròifil bhunaiteach agad
			(air duilleag na “Pròifil”) ach am faigh daoine lorg ort gun duilgheadas.

			Mholamaid gun suidhich thu d’ ainm slàn, gun cuir thu dealbh pròifil ris,
			gun cuir thu “facal-luirg” no dhà ris a’ phròifil (glè fheumail airson caraidean ùra fhaighinn) – agus is dòcha
			an dùthaich far a bheil thu mur eil thu airson a bhith nas mionaidiche na sin.

			Tha suim againn dha do phrìobhaideachd is chan eil gin sam bith dhe na nithean sin riatanach idir.
			Ma tha thu ùr an-seo is mur eil thu eòlach air duine sam bith, b’ urrainn dha na nithean seo
			do chuideachadh ach am cuir thu an aithne air caraidean inntinneach ùra.

			Ma tha thu airson an cunntas agad a sguabadh às uair sam bith, ’s urrainn dhut sin a dhèanamh air %3$s/removeme

			Mòran taing agus fàilte gu %2$s.';
$a->strings['[%s] Notice of remote server domain pattern block list update'] = '[%s] Brath air ùrachadh pàtrain àrainn fhrithealaichean cèine air an liosta-bhacaidh';
$a->strings['Dear %s,

You are receiving this email because the Friendica node at %s where you are registered as a user updated their remote server domain pattern block list.

Please review the updated list at %s at your earliest convenience.'] = '%s, a charaid,

Tha thu a’ faighinn am post-d seo air sgàth ’s gun do rinn an nòd Friendica air %s far a bheil thu clàraichte ’nad cleachdaiche ùrachadh air pàtran àrainn fhrithealaichean cèine air an liosta-bhacaidh.

Dèan lèirmheas air an liosta ùr air %s cho luath ’s a bhios ùine agad.';
$a->strings['Addon not found.'] = 'Cha deach an tuilleadan a lorg.';
$a->strings['Addon %s disabled.'] = 'Tha an tuilleadan %s à comas.';
$a->strings['Addon %s enabled.'] = 'Tha an tuilleadan %s an comas.';
$a->strings['Disable'] = 'Cuir à comas';
$a->strings['Enable'] = 'Cuir an comas';
$a->strings['Administration'] = 'Rianachd';
$a->strings['Addons'] = 'Tuilleadain';
$a->strings['Toggle'] = 'Toglaich';
$a->strings['Author: '] = 'Ùghdar: ';
$a->strings['Maintainer: '] = 'Neach-glèidhidh: ';
$a->strings['Addons reloaded'] = 'Chaidh na tuilleadain ath-luchdadh';
$a->strings['Addon %s failed to install.'] = 'Dh’fhàillig le stàladh an tuilleadain %s.';
$a->strings['Reload active addons'] = 'Ath-luchdaich na tuilleadain ghnìomhach';
$a->strings['There are currently no addons available on your node. You can find the official addon repository at %1$s and might find other interesting addons in the open addon registry at %2$s'] = 'Chan eil tuilleadan ri fhaighinn aig an nòd agad an-dràsta. Gheibh thu ionad-tasgaidh nan tuilleadan oifigeil air %1$s agus dh’fhaoidte gun lorg thu tuilleadain inntinneach eile air an ionad-tasgaidh fhosgailte air %2$s.';
$a->strings['List of all users'] = 'Liosta nan cleachdaichean uile';
$a->strings['Active'] = 'Gnìomhach';
$a->strings['List of active accounts'] = 'Liosta nan cunntasan gnìomhach';
$a->strings['Pending'] = 'Ri dhèiligeadh';
$a->strings['List of pending registrations'] = 'Liosta nan clàraidhean rin dèiligeadh';
$a->strings['Blocked'] = '’Ga bhacadh';
$a->strings['List of blocked users'] = 'Liosta nan cleachdaichean a chaidh a bhacadh';
$a->strings['Deleted'] = 'Air a sguabadh às';
$a->strings['List of pending user deletions'] = 'Liosta nan cleachdaichean rin sguabadh às';
$a->strings['Normal Account Page'] = 'Duilleag àbhaisteach a’ chunntais';
$a->strings['Soapbox Page'] = 'Duilleag cùbaid deasbaid';
$a->strings['Public Forum'] = 'Fòram poblach';
$a->strings['Automatic Friend Page'] = 'Duilleag caraide fhèin-obrachail';
$a->strings['Private Forum'] = 'Fòram prìobhaideach';
$a->strings['Personal Page'] = 'Duilleag phearsanta';
$a->strings['Organisation Page'] = 'Duilleag buidhinn';
$a->strings['News Page'] = 'Duilleag naidheachdan';
$a->strings['Community Forum'] = 'Fòram coimhearsnachd';
$a->strings['Relay'] = 'Ath-sheachadan';
$a->strings['You can\'t block a local contact, please block the user instead'] = 'Chan urrainn dhut neach-aithne ionadail a bhacadh, bac an cleachdaiche ’na àite';
$a->strings['%s contact unblocked'] = [
	0 => 'Chaidh %s neach-aithne a dhì-bhacadh',
	1 => 'Chaidh %s luchd-aithne a dhì-bhacadh',
	2 => 'Chaidh %s luchd-aithne a dhì-bhacadh',
	3 => 'Chaidh %s luchd-aithne a dhì-bhacadh',
];
$a->strings['Remote Contact Blocklist'] = 'Liosta bacadh luchd-aithne cèin';
$a->strings['This page allows you to prevent any message from a remote contact to reach your node.'] = 'Leigidh an duilleag seo leat gum bac thu teachdaireachd sam bith o neach-aithne cèin o ruigsinn an nòid agad.';
$a->strings['Block Remote Contact'] = 'Bac an neach-aithne cèin';
$a->strings['select all'] = 'tagh a h-uile';
$a->strings['select none'] = 'na tagh gin';
$a->strings['Unblock'] = 'Dì-bhac';
$a->strings['No remote contact is blocked from this node.'] = 'Cha deach neach-aithne cèin a bhacadh on nòd seo.';
$a->strings['Blocked Remote Contacts'] = 'Luchd-aithne cèin air am bacadh';
$a->strings['Block New Remote Contact'] = 'Bac neach-aithne cèin ùr';
$a->strings['Photo'] = 'Dealbh';
$a->strings['Reason'] = 'Adhbhar';
$a->strings['%s total blocked contact'] = [
	0 => 'Chaidh %s neach-aithne a bhacadh gu h-iomlan',
	1 => 'Chaidh %s luchd-aithne a bhacadh gu h-iomlan',
	2 => 'Chaidh %s luchd-aithne a bhacadh gu h-iomlan',
	3 => 'Chaidh %s luchd-aithne a bhacadh gu h-iomlan',
];
$a->strings['URL of the remote contact to block.'] = 'URL an neach-aithne chèin ri bhacadh.';
$a->strings['Also purge contact'] = 'Purgaidich an neach-aithne cuideachd';
$a->strings['Removes all content related to this contact from the node. Keeps the contact record. This action cannot be undone.'] = 'Bheir seo air falbh susbaint sam bith a tha co-cheangailte ris an neach-aithne seo on nòd. Cumaidh seo clàr an neach-aithne. Cha ghabh an gnìomh seo a neo-dhèanamh.';
$a->strings['Block Reason'] = 'Adhbhar a’ bhacaidh';
$a->strings['Server domain pattern added to the blocklist.'] = 'Chaidh pàtran àrainn fhrithealaichean a chur ris an liosta-bhacaidh.';
$a->strings['%s server scheduled to be purged.'] = [
	0 => 'Chaidh %s fhrithealaiche a chur air sgeideal a’ phurgaideachaidh.',
	1 => 'Chaidh %s fhrithealaiche a chur air sgeideal a’ phurgaideachaidh.',
	2 => 'Chaidh %s frithealaichean a chur air sgeideal a’ phurgaideachaidh.',
	3 => 'Chaidh %s frithealaiche a chur air sgeideal a’ phurgaideachaidh.',
];
$a->strings['← Return to the list'] = '← Air ais dhan liosta';
$a->strings['Block A New Server Domain Pattern'] = 'Bac pàtran àrainne fhrithealaichean ùr';
$a->strings['<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'] = '<p>Chan eil aire air litrichean mòra is beaga aig pàtran àrainne fhrithealaichean is tha e ’na shaorag slige leis na caractaran sònraichte seo:</p>
<ul>
	<li><code>*</code>: Uiread sam bith de charactaran</li>
	<li><code>?</code>: Aon charactar</li>
</ul>';
$a->strings['Check pattern'] = 'Thoir sùil air a’ phàtran';
$a->strings['Matching known servers'] = 'A’ maidseadh nam frithealaichean as aithne dhuinn';
$a->strings['Server Name'] = 'Ainm an fhrithealaiche';
$a->strings['Server Domain'] = 'Àrainn an fhrithealaiche';
$a->strings['Known Contacts'] = 'An luchd-aithne as aithne dhuinn';
$a->strings['%d known server'] = [
	0 => '%d fhrithealaiche as aithne dhuinn',
	1 => '%d fhrithealaiche as aithne dhuinn',
	2 => '%d frithealaichean as aithne dhuinn',
	3 => '%d frithealaiche as aithne dhuinn',
];
$a->strings['Add pattern to the blocklist'] = 'Cuir am pàtran ris an liosta-bhacaidh';
$a->strings['Server Domain Pattern'] = 'Pàtran àrainne fhrithealaichean';
$a->strings['The domain pattern of the new server to add to the blocklist. Do not include the protocol.'] = 'Am pàtran àrainne fhrithealaichean ùr airson a chur ris an liosta-bhacaidh. Na gabh a-staigh am pròtacal.';
$a->strings['Purge server'] = 'Purgaidich am frithealaiche';
$a->strings['Also purges all the locally stored content authored by the known contacts registered on that server. Keeps the contacts and the server records. This action cannot be undone.'] = [
	0 => 'Nì seo purgaideachadh air gach susbaint cuideachd a chaidh a stòradh gu h-ionadail ’s a chaidh a sgrìobhadh leis an luchd-aithne as aithne dhuinn a tha clàraichte air an fhrithealaiche ud. Cumaidh seo luchd-aithne is clàran an fhrithealaiche. Cha ghabh seo a neo-dhèanamh.',
	1 => 'Nì seo purgaideachadh air gach susbaint cuideachd a chaidh a stòradh gu h-ionadail ’s a chaidh a sgrìobhadh leis an luchd-aithne as aithne dhuinn a tha clàraichte air na frithealaichean ud. Cumaidh seo luchd-aithne is clàran nam frithealaichean. Cha ghabh seo a neo-dhèanamh.',
	2 => 'Nì seo purgaideachadh air gach susbaint cuideachd a chaidh a stòradh gu h-ionadail ’s a chaidh a sgrìobhadh leis an luchd-aithne as aithne dhuinn a tha clàraichte air na frithealaichean ud. Cumaidh seo luchd-aithne is clàran nam frithealaichean. Cha ghabh seo a neo-dhèanamh.',
	3 => 'Nì seo purgaideachadh air gach susbaint cuideachd a chaidh a stòradh gu h-ionadail ’s a chaidh a sgrìobhadh leis an luchd-aithne as aithne dhuinn a tha clàraichte air na frithealaichean ud. Cumaidh seo luchd-aithne is clàran nam frithealaichean. Cha ghabh seo a neo-dhèanamh.',
];
$a->strings['Block reason'] = 'Adhbhar a’ bhacaidh';
$a->strings['The reason why you blocked this server domain pattern. This reason will be shown publicly in the server information page.'] = 'An t-adhbhar air an do bhac thu am pàtran àrainne fhrithealaichean seo. Thèid an t-adhbhar a shealltainn gu poblach air duilleag fiosrachadh an fhrithealaiche.';
$a->strings['Error importing pattern file'] = 'Mearachd ag ion-phortadh faidhle nam pàtranan';
$a->strings['Local blocklist replaced with the provided file.'] = 'Chaidh am faidhle a sholair thu a chur an àite na liosta-bacaidh ionadail.';
$a->strings['%d pattern was added to the local blocklist.'] = [
	0 => 'Chaidh %d phàtran a chur ris an liosta-bhacaidh ionadail.',
	1 => 'Chaidh %d phàtran a chur ris an liosta-bhacaidh ionadail.',
	2 => 'Chaidh %d pàtranan a chur ris an liosta-bhacaidh ionadail.',
	3 => 'Chaidh %d pàtran a chur ris an liosta-bhacaidh ionadail.',
];
$a->strings['No pattern was added to the local blocklist.'] = 'Cha deach pàtran sam bith a chur ris an liosta-bhacaidh ionadail.';
$a->strings['Import a Server Domain Pattern Blocklist'] = 'Ion-phortaich liosta-bhacaidh le pàtrain àrainne fhrithealaichean';
$a->strings['<p>This file can be downloaded from the <code>/friendica</code> path of any Friendica server.</p>'] = '<p>Gabhaidh am faidhle seo a luchdadh a-nuas on t-slighe <code>/friendica</code> aig frithealaiche Friendica sam bith.</p>';
$a->strings['Upload file'] = 'Luchdaich suas faidhle';
$a->strings['Patterns to import'] = 'Na pàtranan rin ion-phortadh';
$a->strings['Domain Pattern'] = 'Pàtran àrainne';
$a->strings['Import Mode'] = 'Modh an ion-phortaidh';
$a->strings['Import Patterns'] = 'Ion-phortaich na pàtranan';
$a->strings['%d total pattern'] = [
	0 => '%d phàtran gu h-iomlan',
	1 => '%d phàtran gu h-iomlan',
	2 => '%d pàtranan gu h-iomlan',
	3 => '%d pàtran gu h-iomlan',
];
$a->strings['Server domain pattern blocklist CSV file'] = 'Faidhle CSV sa bheil liosta-bhacaidh le pàtrain àrainne fhrithealaichean';
$a->strings['Append'] = 'Cuir ris';
$a->strings['Imports patterns from the file that weren\'t already existing in the current blocklist.'] = 'Nì seo ion-phortadh dhe na pàtranan san fhaidhle nach eil air an liosta-bhacaidh làithreach fhathast.';
$a->strings['Replace'] = 'Cuir ’na àite';
$a->strings['Replaces the current blocklist by the imported patterns.'] = 'Cuiridh seo na pàtranan air an ion-phortadh an àite na liosta-bacaidh làithrich.';
$a->strings['Blocked server domain pattern'] = 'Pàtran àrainne fhrithealaichean a chaidh a bhacadh';
$a->strings['Reason for the block'] = 'Adhbhar a’ bhacaidh';
$a->strings['Delete server domain pattern'] = 'Sguab às am pàtran àrainne fhrithealaichean';
$a->strings['Check to delete this entry from the blocklist'] = 'Cuir cromag ris a sguabadh às an nì seo on liosta-bhacaidh';
$a->strings['Server Domain Pattern Blocklist'] = 'Liosta-bhacaidh le pàtrain àrainne fhrithealaichean';
$a->strings['This page can be used to define a blocklist of server domain patterns from the federated network that are not allowed to interact with your node. For each domain pattern you should also provide the reason why you block it.'] = '’S urrainn dhut an duilleag seo a chleachdadh airson liosta-bhacaidh le pàtrain àrainne fhrithealaichean on lìonra cho-naisgte a mhìneachadh nach fhaod gnìomh a ghabhail leis an nòd agad. Airson gach pàtran àrainne fhrithealaichean, bu chòir dhut an t-adhbhar a thoirt seachad air an do bhac thu e.';
$a->strings['The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'] = 'Thèid liosta nam pàtran àrainne fhrithealaichean foillseachadh gu poblach air an duilleag <a href="/friendica">/friendica</a> ach am faigh an luchd-cleachdaidh agad ’s na daoine a tha a’ sgrùdadh duilgheadasan conaltraidh a-mach gun duilgheadas dè as adhbhar.';
$a->strings['Import server domain pattern blocklist'] = 'Ion-phortaich liosta-bhacaidh le pàtrain àrainne fhrithealaichean';
$a->strings['Add new entry to the blocklist'] = 'Cuir nì ùr ris an liosta-bhacaidh';
$a->strings['Save changes to the blocklist'] = 'Sàbhail na h-atharraichean air an liosta-bhacaidh';
$a->strings['Current Entries in the Blocklist'] = 'Na nithean làithreach air an liosta-bhacaidh';
$a->strings['Delete entry from the blocklist'] = 'Sguab às an nì on liosta-bhacaidh';
$a->strings['Delete entry from the blocklist?'] = 'A bheil thu airson an nì seo a sguabadh às on liosta-bhacaidh?';
$a->strings['Update has been marked successful'] = 'Chaidh comharrachadh gun do soirbhich leis an ùrachadh';
$a->strings['Database structure update %s was successfully applied.'] = 'Chaidh ùrachadh %s air structar an stòir-dhàta a chur an sàs.';
$a->strings['Executing of database structure update %s failed with error: %s'] = 'Dh’fhàillig an t-ùrachadh %s air structar an stòir-dhàta leis a’ mhearachd seo: %s';
$a->strings['Executing %s failed with error: %s'] = 'Dh’fhàillig gnìomhachadh %s leis a’ chòd mhearachd seo: %s';
$a->strings['Update %s was successfully applied.'] = 'Chaidh ùrachadh %s a chur an sàs.';
$a->strings['Update %s did not return a status. Unknown if it succeeded.'] = 'Cha do thill an t-ùrachadh %s staid. Chan eil fhios an do shoirbhich leis gus nach do shoirbhich.';
$a->strings['There was no additional update function %s that needed to be called.'] = 'Cha robh foincsean ùrachaidh %s eile feumach air gairm ann.';
$a->strings['No failed updates.'] = 'Cha do dh’fhàillig le ùrachadh sam bith.';
$a->strings['Check database structure'] = 'Thoir sùil air structar an stòir-dhàta';
$a->strings['Failed Updates'] = 'Na dh’ùrachaidhean a dh’fhàillig leotha';
$a->strings['This does not include updates prior to 1139, which did not return a status.'] = 'Cha ghabh seo a-staigh na h-ùrachaidhean ro 1139 nach do thilleadh staid.';
$a->strings['Mark success (if update was manually applied)'] = 'Cuir comharra gun do shoirbhich leis (ma rinn thu an t-ùrachadh a làimh)';
$a->strings['Attempt to execute this update step automatically'] = 'Feuch gnìomhachadh a’ cheuma seo dhen ùrachadh gu fèin-obrachail';
$a->strings['Lock feature %s'] = 'Glais gleus %s';
$a->strings['Manage Additional Features'] = 'Stiùir na gleusan a bharrachd';
$a->strings['Other'] = 'Eile';
$a->strings['unknown'] = 'chan eil fhios';
$a->strings['%s total systems'] = 'Siostaman gu h-iomlan: %s';
$a->strings['%s active users last month'] = 'Cleachdaichean gnìomhach sa mhìos seo chaidh: %s';
$a->strings['%s active users last six months'] = 'Cleachdaichean gnìomhach san leth-bhliadhna seo chaidh: %s';
$a->strings['%s registered users'] = 'Cleachdaichean clàraichte: %s';
$a->strings['%s locally created posts and comments'] = 'Postaichean is beachdan a chaidh a chruthachadh gu h-ionadail: %s';
$a->strings['%s posts per user'] = 'Postaichean gach cleachdaiche: %s';
$a->strings['%s users per system'] = 'Cleachdaichean gach siostaim: %s';
$a->strings['This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.'] = 'Bheir an duilleag seo àireamhan dhut mun chuid dhen lìonra shòisealta cho-naisgte sa bheil an nòd seo dhe Friendica. Chan eil na h-àireamhan seo coileanta is cha sheall iad ach a’ phàirt dhen lìonra air a bheil an nòd agad eòlach.';
$a->strings['Federation Statistics'] = 'Stadastaireachd a’ cho-nasgaidh';
$a->strings['Currently this node is aware of %s nodes (%s active users last month, %s active users last six months, %s registered users in total) from the following platforms:'] = 'Tha an nòd seo eòlach air %s nòd(an) aig an àm seo (cleachdaichean gnìomhach sa mhìos seo chaidh: %s, cleachdaichean gnìomhach san leth-bhliadhna seo chaidh: %s, cleachdaichean clàraichte: %s gu h-iomlan) o na h-ùrlaran a leanas:';
$a->strings['Item marked for deletion.'] = 'Chaidh an nì a chomharrachadh a chùm sguabaidh às.';
$a->strings['Delete Item'] = 'Sguab às an nì';
$a->strings['Delete this Item'] = 'Sguab às an nì seo';
$a->strings['On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'] = 'Air an duilleag seo, ’s urrainn dhut nì a sguabadh às on nòd agad. Mas e post ciad ìre a tha san nì, thèid an snàithlean gu lèir a sguabadh às.';
$a->strings['You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'] = 'Feumaidh tu a bhith eòlach air GUID an nì. Gheibh thu lorg air m. e. a’ coimhead air URL an t-seallaidh. ’S e a’ phàirt mu dheireadh aig http://example.com/display/123456 a sa san GUID, ’s e 123456 a th’ ann an-seo.';
$a->strings['GUID'] = 'GUID';
$a->strings['The GUID of the item you want to delete.'] = 'GUID an nì a tha thu airson sguabadh às.';
$a->strings['Item Source'] = 'Tùs an nì';
$a->strings['Item Guid'] = 'GUID an nì';
$a->strings['Item Id'] = 'ID an nì';
$a->strings['Item URI'] = 'URI an nì';
$a->strings['Terms'] = 'Briathran';
$a->strings['Tag'] = 'Taga';
$a->strings['Type'] = 'Seòrsa';
$a->strings['Term'] = 'Briathar';
$a->strings['URL'] = 'URL';
$a->strings['Mention'] = 'Iomradh';
$a->strings['Implicit Mention'] = 'Iomradh fillte';
$a->strings['The logfile \'%s\' is not writable. No logging possible'] = 'Cha ghabh sgrìobhadh ann am faidhle “%s” an loga. Cha ghabh logadh a dhèanamh';
$a->strings['PHP log currently enabled.'] = 'Tha logadh PHP an comas an-dràsta.';
$a->strings['PHP log currently disabled.'] = 'Tha logadh PHP à comas an-dràsta.';
$a->strings['Logs'] = 'Logaichean';
$a->strings['Clear'] = 'Falamhaich';
$a->strings['Enable Debugging'] = 'Cuir dì-bhugachadh an comas';
$a->strings['Log file'] = 'Faidhle an loga';
$a->strings['Must be writable by web server. Relative to your Friendica top-level directory.'] = 'Feumaidh cead sgrìobhaidh a bhith aig an fhrithealaiche-lìn. Dàimheach ri prìomh-pasgan Friendica.';
$a->strings['Log level'] = 'Leibheil an loga';
$a->strings['PHP logging'] = 'Logadh PHP';
$a->strings['To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the \'error_log\' line is relative to the friendica top-level directory and must be writeable by the web server. The option \'1\' for \'log_errors\' and \'display_errors\' is to enable these options, set to \'0\' to disable them.'] = 'Airson logadh nam mearachdan is rabhaidhean PHP a chur an comas gu sealach, ’s urrainn dhut na leanas a chur air thoiseach faidhle index.php an stàlaidh agad. Tha ainm an fhaidhle a tha ’ga shuidheachadh air an loidhne \'error_log\' dàimheach ri prìomh-phasgan Friendica agus feumaidh cead sgrìobhaidh a bhith aig an fhrithealaiche-lìn ann. Cuiridh tu \'log_errors\' (logadh nam mearachdan) agus \'display_errors\' (sealltainn nam mearachdan) an comas leis an roghainn \'1\' agus à comas leis an roghainn \'0\'.';
$a->strings['Error trying to open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s exist and is readable.'] = 'Mearachd a’ feuchainn ri faidhle <strong>%1$s</strong> an loga fhosgladh.<br/>Dearbh gu bheil am faidhle %1$s ann is gun gabh a leughadh.';
$a->strings['Couldn\'t open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s is readable.'] = 'Cha b’ urrainn dhinn faidhle <strong>%1$s</strong> an loga fhosgladh.<br/>Dearbh gun gabh am faidhle %1$s a leughadh.';
$a->strings['View Logs'] = 'Seall na logaichean';
$a->strings['Search in logs'] = 'Lorg sna logaichean';
$a->strings['Show all'] = 'Seall na h-uile';
$a->strings['Date'] = 'Ceann-là';
$a->strings['Level'] = 'Leibheil';
$a->strings['Context'] = 'Co-theacsa';
$a->strings['ALL'] = 'NA h-UILE';
$a->strings['View details'] = 'Seall am mion-fhiosrachadh';
$a->strings['Click to view details'] = 'Briog air a shealltainn a’ mhion-fhiosrachaidh';
$a->strings['Data'] = 'Dàta';
$a->strings['Source'] = 'Tùs';
$a->strings['File'] = 'Faidhle';
$a->strings['Line'] = 'Loidhne';
$a->strings['Function'] = 'Foincsean';
$a->strings['UID'] = 'UID';
$a->strings['Process ID'] = 'ID a’ phròiseis';
$a->strings['Close'] = 'Dùin';
$a->strings['Inspect Deferred Worker Queue'] = 'Sgrùd ciutha nan obraichean dàilichte';
$a->strings['This page lists the deferred worker jobs. This are jobs that couldn\'t be executed at the first time.'] = 'Gheibh thu liosta nan obraichean dàilichte air an duilleag seo. Cha b’ urrainn dhuinn na h-obraichean seo a dhèanamh sa chiad uair.';
$a->strings['Inspect Worker Queue'] = 'Sgrùd ciutha nan obraichean';
$a->strings['This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.'] = 'Gheibh thu liosta nan obraichean a tha sa chiutha an-dràsta air an duilleag seo. Thèid na h-obraichean seo a làimhseachadh leis a’ cronjob a shuidhich thu rè an stàlaidh.';
$a->strings['ID'] = 'ID';
$a->strings['Command'] = 'Àithne';
$a->strings['Job Parameters'] = 'Paramadairean na h-obrach';
$a->strings['Priority'] = 'Prìomhachas';
$a->strings['No special theme for mobile devices'] = 'Chan eil ùrlar sònraichte do dh’uidheaman mobile ann';
$a->strings['%s - (Experimental)'] = '%s – (deuchainneil)';
$a->strings['No community page for local users'] = 'Chan eil duilleag coimhearsnachd do chleachdaichean ionadail ann';
$a->strings['No community page'] = 'Gun duilleag coimhearsnachd';
$a->strings['Public postings from users of this site'] = 'Postaichean poblach nan cleachdaichean air an làrach seo';
$a->strings['Public postings from the federated network'] = 'Postaichean poblach on lìonra cho-naisgte';
$a->strings['Public postings from local users and the federated network'] = 'Postaichean poblach nan cleachdaichean ionadail ’s on lìonra cho-naisgte';
$a->strings['Multi user instance'] = 'Ionstans ioma-chleachdaiche';
$a->strings['Closed'] = 'Dùinte';
$a->strings['Requires approval'] = 'Tha feum air aontachadh';
$a->strings['Open'] = 'Fosgailte';
$a->strings['No SSL policy, links will track page SSL state'] = 'Gun phoileasaidh SSL, nì ceanglaichean tracadh air staid SSL na duilleige';
$a->strings['Force all links to use SSL'] = 'Èignich gun chleachd a h-uile ceangal SSL';
$a->strings['Self-signed certificate, use SSL for local links only (discouraged)'] = 'Teisteanas air fhèin-shoidhneadh, cleachd SSL do cheanglaichean ionadail a-mhàin (cha mholamaid seo)';
$a->strings['Don\'t check'] = 'Na cuir sùil';
$a->strings['check the stable version'] = 'cuir sùil air na tionndadh seasmhach';
$a->strings['check the development version'] = 'cuir sùil air na tionndadh leasachaidh';
$a->strings['none'] = 'chan eil gin';
$a->strings['Local contacts'] = 'Luchd-aithne an ionadail';
$a->strings['Site'] = 'Làrach';
$a->strings['General Information'] = 'Fiosrachadh coitcheann';
$a->strings['Republish users to directory'] = 'Ath-fhoillsich na cleachdaichean dhan eòlaire';
$a->strings['Registration'] = 'Clàradh';
$a->strings['File upload'] = 'Luchdadh suas fhaidhlichean';
$a->strings['Policies'] = 'Poileasaidhean';
$a->strings['Auto Discovered Contact Directory'] = 'Eòlaire an luchd-aithne a chaidh a lorg gu fèin-obrachail';
$a->strings['Performance'] = 'Dèanadas';
$a->strings['Worker'] = 'Obraiche';
$a->strings['Message Relay'] = 'Ath-sheachadan theachdaireachdan';
$a->strings['Use the command "console relay" in the command line to add or remove relays.'] = 'Cleachd an àithne “console relay” air an loidhne-àithne a chur ris no a thoirt air falbh ath-sheachadain.';
$a->strings['The system is not subscribed to any relays at the moment.'] = 'Cha do rinn an siostam fo-sgrìobhadh air ath-sheachadan sam bith aig an àm seo.';
$a->strings['The system is currently subscribed to the following relays:'] = 'Tha an siostam a’ fo-sgrìobhadh dha na h-ath-sheachadain seo:';
$a->strings['Relocate Node'] = 'Imrich an nòd';
$a->strings['Relocating your node enables you to change the DNS domain of this node and keep all the existing users and posts. This process takes a while and can only be started from the relocate console command like this:'] = 'Le imrich an nòd, ’s urrainn dhut àrainn DNS an nòid seo atharrachadh agus na cleachdaichean is postaichean uile a chumail. Bheir am pròiseas seo greis mhath is cha ghabh a thòiseachadh ach le àithne imrich na consoil mar seo:';
$a->strings['(Friendica directory)# bin/console relocate https://newdomain.com'] = '(Pasgan Friendica)# bin/console relocate https://àrainn-ùr.com';
$a->strings['Site name'] = 'Ainm na làraich';
$a->strings['Sender Email'] = 'Post-d an t-seòladair';
$a->strings['The email address your server shall use to send notification emails from.'] = 'An seòladh puist-d a chleachdas am frithealaiche airson brathan puist-d a chur uaithe.';
$a->strings['Name of the system actor'] = 'Ainm actar an t-siostaim';
$a->strings['Name of the internal system account that is used to perform ActivityPub requests. This must be an unused username. If set, this can\'t be changed again.'] = 'Ainm cunntas inntearnail an fhrithealaiche a thèid a chleachdadh airson iarrtasan ActivityPub. Feumaidh seo a bhith ’na ainm-chleachdaiche gun chleachdadh. Nuair a bhios seo air a shuidheachadh, gha ghabh atharrachadh a-rithist.';
$a->strings['Banner/Logo'] = 'Bratach/Suaicheantas';
$a->strings['Email Banner/Logo'] = 'Bratach/Suaicheantas puist-d';
$a->strings['Shortcut icon'] = 'Ìomhaigheag na h-ath-ghoirid';
$a->strings['Link to an icon that will be used for browsers.'] = 'Ceangal gu ìomhaigheag a thèid a chleachdadh le brabhsairean.';
$a->strings['Touch icon'] = 'Ìomhaigheag suathaidh';
$a->strings['Link to an icon that will be used for tablets and mobiles.'] = 'Ceangal gu ìomhaigheag a thèid a chleachdadh airson tablaidean is fònaichean-làimhe.';
$a->strings['Additional Info'] = 'Barrachd fiosrachaidh';
$a->strings['For public servers: you can add additional information here that will be listed at %s/servers.'] = 'Airson frithealaichean poblach: ’s urrainn dhut barrachd fiosrachaidh a chur ris an-seo a nochdas air %s/servers.';
$a->strings['System language'] = 'Cànan an t-siostaim';
$a->strings['System theme'] = 'Ùrlar an t-siostaim';
$a->strings['Default system theme - may be over-ridden by user profiles - <a href="%s" id="cnftheme">Change default theme settings</a>'] = 'Ùrlar bunaiteach an t-siostaim – gabhaidh a thar-àithneadh le pròifilean cleachdaiche – <a href="%s" id="cnftheme">Atharraich roghainnean an ùrlair bhunaitich</a>';
$a->strings['Mobile system theme'] = 'Ùrlar mobile an t-siostaim';
$a->strings['Theme for mobile devices'] = 'An t-ùrlar do dh’uidheaman mobile';
$a->strings['SSL link policy'] = 'Poileasaidh SSL nan ceanglaichean';
$a->strings['Determines whether generated links should be forced to use SSL'] = 'Suidhichidh seo an dèid SSL a sparradh air ceanglaichean a thèid a ghintinn gus nach dèid';
$a->strings['Force SSL'] = 'Spàrr SSL';
$a->strings['Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.'] = 'Spàrr SSL air a h-uile iarrtas gun SSL – An aire: dh’fhaoidte gun adhbharaich seo lùban gun chrìoch air cuid a shiostaman.';
$a->strings['Show help entry from navigation menu'] = 'Seall nì na cobharach ann an clàr-taice na seòladaireachd';
$a->strings['Displays the menu entry for the Help pages from the navigation menu. It is always accessible by calling /help directly.'] = 'Seallaidh an nì airson duilleagan na cobharach ann an clàr-taice na seòladaireachd. Gabhaidh inntrigeadh le gairm /help gu dìreach an-còmhnaidh.';
$a->strings['Single user instance'] = 'Ionstans aon-chleachdaiche';
$a->strings['Make this instance multi-user or single-user for the named user'] = 'Dèan ionstans ioma-chleachdaiche no aon-chleachdaiche dhan chleachdaiche ainmichte dhen ionstans seo';
$a->strings['Maximum image size'] = 'Meud as motha nan dealbhan';
$a->strings['Maximum size in bytes of uploaded images. Default is 0, which means no limits.'] = 'Am meud as motha ann am baidht do dhealbhan a thèid a luchdadh suas. Is 0 a’ bhun-roghainn, ’s e sin gun chrìoch.';
$a->strings['Maximum image length'] = 'Faide as motha nan dealbhan';
$a->strings['Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.'] = 'An fhaide as motha ann am piogsail aig an taobh as fhaide do dhealbhan a thèid a luchdadh suas. Is -1 a’ bhun-roghainn, ’s e sin gun chrìoch.';
$a->strings['JPEG image quality'] = 'Càileachd deilbh JPEG';
$a->strings['Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.'] = 'Thèid dealbhan a’ sàbhaladh leis a’ chàileachd JPEG seo às dèidh an luchdadh suas [0-100]. Is 100 a’ bhun-roghainn, ’s e sin a’ chàileachd shlàn.';
$a->strings['Register policy'] = 'Poileasaidh clàraidh';
$a->strings['Maximum Daily Registrations'] = 'An àireamh as motha de chlàraidhean gach latha';
$a->strings['If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.'] = 'Ma tha an clàradh ceadaichte gu h-àrd, suidhichidh seo an àireamh as motha de chlàraidhean chleachdaichean ùra gach latha. Ma tha an clàradh dùinte, cha doir an roghainn seo buaidh.';
$a->strings['Register text'] = 'Teacsa a’ chlàraidh';
$a->strings['Will be displayed prominently on the registration page. You can use BBCode here.'] = 'Thèid a shealltainn gu follaiseach air duilleag a’ chlàraidh. ’S urrainn dhut BBCode a chleachdadh an-seo.';
$a->strings['Forbidden Nicknames'] = 'Far-ainmean toirmisgte';
$a->strings['Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.'] = 'Liosta sgaraichte le cromagan de dh’fhar-ainmean nach fhaod clàradh. Tha liosta de dh’ainmean dreuchdan a-rèir RFC 2142 ro-shocraichte.';
$a->strings['Accounts abandoned after x days'] = 'Cunntasan air an trèigsinn às dèidh x là';
$a->strings['Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.'] = 'Cha chaith seo goireasan an t-siostam le ceasnachadh làraichean air an taobh a-muigh airson cunntasan air an trèigsinn. Cuir a-steach 0 airson cunntasan gun chrìoch ùine.';
$a->strings['Allowed friend domains'] = 'Àrainnean ceadaichte do chàirdeasan';
$a->strings['Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains'] = 'Liosta sgaraichte le cromagan de dh’àrainnean a dh’fhaodas càirdeasan a stèidheachadh leis an làrach seo. Gabhaidh seo ri saoragan. Fàg bàn airson a h-uile àrainn a cheadachadh';
$a->strings['Allowed email domains'] = 'Àrainnean puist-d ceadaichte';
$a->strings['Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains'] = 'Liosta sgaraichte le cromagan de dh’àrainnean a tha ceadaichte ann am puist-d airson clàradh leis an làrach seo. Gabhaidh seo ri saoragan. Fàg bàn airson a h-uile àrainn a cheadachadh';
$a->strings['No OEmbed rich content'] = 'Gun susbaint OEmbed bheartach';
$a->strings['Don\'t show the rich content (e.g. embedded PDF), except from the domains listed below.'] = 'Na seall susbaint bheartach (m. e. PDF leabaichte) ach o na h-àrainnean air an liosta gu h-ìosal.';
$a->strings['Trusted third-party domains'] = 'Àrainnean threas-phàrtaidhea earbsach';
$a->strings['Comma separated list of domains from which content is allowed to be embedded in posts like with OEmbed. All sub-domains of the listed domains are allowed as well.'] = 'Liosta sgaraichte le cromagan de dh’àrainnean on a dh’fhaodar susbaint a leabachadh am broinn phostaichean le OEmbed. Thèid cead a thoirt dhan a h-uile fo-àrainn dhe na h-àrainnean air an liosta cuideachd.';
$a->strings['Block public'] = 'Bac inntrigeadh poblach';
$a->strings['Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.'] = 'Cuir cromag ris a bhacadh inntrigeadh poblach air a h-uile duilleag phearsanta a bhiodh poblach air an làrach seo ach dhan fheadhainn a bhios air a clàradh a-staigh.';
$a->strings['Force publish'] = 'Spàrr foillseachadh';
$a->strings['Check to force all profiles on this site to be listed in the site directory.'] = 'Cuir cromag ris a sparradh air a h-uile pròifil air an làrach seo gun nochd iad ann an eòlaire na làraich.';
$a->strings['Enabling this may violate privacy laws like the GDPR'] = 'Ma chuireas tu seo an comas, dh’fhaoidte gum bris thu laghan mar an GDPR';
$a->strings['Global directory URL'] = 'URL an eòlaire cho-naisgte';
$a->strings['URL to the global directory. If this is not set, the global directory is completely unavailable to the application.'] = 'URL dhan eòlaire cho-naisgte. Mura deach seo a shuidheachadh, cha bhi an t-eòlaire uile-choitcheann ri fhaighinn idir dhan aplacaid.';
$a->strings['Private posts by default for new users'] = 'Postaichean prìobhaideach ’na bhun-roghainn do chleachdaichean ùra';
$a->strings['Set default post permissions for all new members to the default privacy group rather than public.'] = 'Suidhichidh seo ceadan phostaichean nam ball ùra air a’ bhuidheann phrìobhaideach gu bunaiteach seach air a’ bhuidheann phoblach.';
$a->strings['Don\'t include post content in email notifications'] = 'Na gabh a-staigh susbaint nam post ann am brathan puist-d';
$a->strings['Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.'] = 'Na gabh a-staigh susbaint puist/beachd/teachdaireachd prìobhaidich/msaa. sna brathan puist-d a thèid a chur on làrach seo, a chùm prìobhaideachd.';
$a->strings['Disallow public access to addons listed in the apps menu.'] = 'Na ceadaich inntrigeadh poblach dha na tuilleadain a tha air an liosta ann an clàr-taice nan aplacaidean.';
$a->strings['Checking this box will restrict addons listed in the apps menu to members only.'] = 'Ma tha cromag sa bhogsa seo, bidh an t-inntrigeadh air na tuilleadain a chithear ann an clàr-taice nan aplacaidean cuingichte air na buill a-mhàin.';
$a->strings['Don\'t embed private images in posts'] = 'Na leabaich dealbhan prìobhaideach ann am postaichean';
$a->strings['Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.'] = 'Na cuir lethbhreac leabaichte dhen dealbh an àite dhealbhan prìobhaideach ann am postaichean a tha ’gan òstadh gu h-ionadail. Is ciall dha seo gum feum an luchd-aithne a gheibh postaichean sa bheil dealbhan prìobhaideach ùghdarrachadh is gach dealbh a luchdadh fa leth agus dh’fhaoidte gun doir sin greis.';
$a->strings['Explicit Content'] = 'Susbaint inbheach';
$a->strings['Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.'] = 'Suidhich seo a dh’innse gu bheil an nòd agad ’ga chleachdadh airson susbaint inbheach gu h-àraidh is nach eil e iomchaidh do mhion-aoisich ’s dòcha. Thèid am fiosrachadh seo fhoillseachadh ann am fiosrachadh an nòid agus gabhaidh a chleachdadh m.e. leis an eòlaire cho-naisgte airson an nòd agad a chriathradh air falbh o liosta nan nòdan a chùm ballrachd ùir. A bharrachd air sin, thèid nòta a shealltainn aig duilleag clàradh nan cleachdaichean.';
$a->strings['Proxify external content'] = 'Susbaint chèin tro phrogsaidh';
$a->strings['Route external content via the proxy functionality. This is used for example for some OEmbed accesses and in some other rare cases.'] = 'Rùtaich susbaint chèin le gleus a’ phrogsaidh. Tha seo ’ga chleachdadh, mar eisimpleir, airson cuid dhen inntrigeadh OEmbed agus ann an suidheachaidhean ainneamh eile.';
$a->strings['Cache contact avatars'] = 'Cuir avataran an luchd-aithne dhan tasgadan';
$a->strings['Locally store the avatar pictures of the contacts. This uses a lot of storage space but it increases the performance.'] = 'Stòr dealbhan avatar an luchd-aithne gu h-ionadail. Cleachdaidh seo tòrr àite san stòras ach cuiridh e ris an dèanadas.';
$a->strings['Allow Users to set remote_self'] = 'Leig le cleachdaichean remote_self a shuidheachadh';
$a->strings['With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.'] = 'Ma chuireas tu cromag ris, faodaidh gach cleachdaiche neach-aithne sam bith a chomharrachadh mar remote_self ann an còmhradh càradh an luchd-aithne. Nuair a thèid a’ bhratach seo a chur ri neach-aithne, thèid a h-uile post an neach-aithne sin sgàthanachadh ann an sruth a’ chlechdaiche.';
$a->strings['Enable multiple registrations'] = 'Cuir clàradh iomadach an comas';
$a->strings['Enable users to register additional accounts for use as pages.'] = 'Bheir seo an comas dha na cleachdaichean gun clàraich iad cunntasan a bharrachd airson an cleachdadh ’nan duilleagan.';
$a->strings['Enable OpenID'] = 'Cuir OpenID an comas';
$a->strings['Enable OpenID support for registration and logins.'] = 'Cuir an comas taic dha OpenID airson clàradh is clàradh a-steach.';
$a->strings['Enable Fullname check'] = 'Cuir an comas dearbhadh ainm shlàin';
$a->strings['Enable check to only allow users to register with a space between the first name and the last name in their full name.'] = 'Cuid an comas an dearbhadh nach leig le cleachdaichean clàradh ach le beàrn eadar ainm is sloinneadh an ainm shlàin.';
$a->strings['Community pages for visitors'] = 'Duilleagan coimhearsnachd do dh’aoighean';
$a->strings['Which community pages should be available for visitors. Local users always see both pages.'] = 'Dè na duilleagan coimhearsnachd a chì aoighean. Chì na cleachdaichean ionadail an dà dhuilleag an-còmhnaidh.';
$a->strings['Posts per user on community page'] = 'Postaichean gach cleachdaiche air duilleag na coimhearsnachd';
$a->strings['The maximum number of posts per user on the community page. (Not valid for "Global Community")'] = 'An àireamh as motha de phostaichean aig gach cleachdaiche air duilleag na coimhearsnachd. (Chan eil seo dligheach dhan “Choimhearsnachd cho-naisgte”)';
$a->strings['Maximum system load before delivery and poll processes are deferred - default %d.'] = 'Eallach as motha air an t-siostam mus dèid dàil a chur air an lìbhrigeadh is air pròiseasadh cunbhalach – ’s e %d a tha sa bhun-roghainn.';
$a->strings['Periodically optimize tables like the cache and the workerqueue'] = 'Pisich clàran mar an tasgadan is an ciutha-obrach gu cunbhalach';
$a->strings['Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.'] = 'Lorg san eòlaire ionadail seach san eòlaire cho-naisgte. Nuair a nì thu lorg gu h-ionadail, thèid gach lorg a ghnìomhachadh san eòlaire cho-naisgte sa chùlaibh. Cuiridh seo piseach air na toraidhean luirg nuair a nithear an t-aon lorg a-rithist.';
$a->strings['Remove old remote items, orphaned database records and old content from some other helper tables.'] = 'Thoir air falbh nithean cèine, reacordan stòir-dhàta a tha ’nan dìlleachdanan agus seann-susbaint eile o chuid a chlàran-taice eile.';
$a->strings['Posts from %s can\'t be shared'] = 'Cha ghabh postaichean o %s a co-roinneadh';
$a->strings['Posts from %s can\'t be unshared'] = 'Cha ghabh sgur de cho-roinneadh phostaichean o %s';
$a->strings['Inspect Deferred Workers'] = 'Sgrùd na h-obraichean dàilichte';
$a->strings['Daily posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Ràinig thu a’ chrìoch de %d phost gach latha. Chaidh am post a dhiùltadh.',
	1 => 'Ràinig thu a’ chrìoch de %d phost gach latha. Chaidh am post a dhiùltadh.',
	2 => 'Ràinig thu a’ chrìoch de %d postaichean gach latha. Chaidh am post a dhiùltadh.',
	3 => 'Ràinig thu a’ chrìoch de %d post gach latha. Chaidh am post a dhiùltadh.',
];
$a->strings['Weekly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Ràinig thu a’ chrìoch de %d phost gach seachdain. Chaidh am post a dhiùltadh.',
	1 => 'Ràinig thu a’ chrìoch de %d phost gach seachdain. Chaidh am post a dhiùltadh.',
	2 => 'Ràinig thu a’ chrìoch de %d postaichean gach seachdain. Chaidh am post a dhiùltadh.',
	3 => 'Ràinig thu a’ chrìoch de %d post gach seachdain. Chaidh am post a dhiùltadh.',
];
$a->strings['Monthly posting limit of %d post reached. The post was rejected.'] = 'Ràinig thu a’ chrìoch de %d post gach mìos. Chaidh am post a dhiùltadh.';
$a->strings['Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).'] = 'Ghabh thu fhèin agus <strong>%s</strong> gnìomh gu poblach leis an luchd-aithne seo (leantainn, beachd air no gur toigh leibh post poblach).';
$a->strings['Failed to update contact record.'] = 'Cha b’ urrainn dhuinn clàr an neach-aithne ùrachadh.';
$a->strings['Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'] = 'Faigh fiosrachadh mar dhealbhan ro-sheallaidh, tiotal is tàladh o nì an inbhir. ’S urrainn dhut seo a chur an comas mur eil cus teacsa san inbhir. Thèid faclan-luirg a thogail o bhann-cinn nì an inbhir agus am postadh ’nan tagaichean hais.';
$a->strings['Native reshare'] = 'Co-roinneadh tùsail';
$a->strings['Replies/likes to your public posts <strong>may</strong> still be visible'] = '<strong>Dh’fhaoidte</strong> gum faicear freagairtean/gur toigh le daoine na postaichean poblach agad fhathast';
$a->strings['Global Community'] = 'Coimhearsnachd cho-naisgte';
$a->strings['Starred'] = 'Rionnag';
$a->strings['Your profile will also be published in the global friendica directories (e.g. <a href="%s">%s</a>).'] = 'Thèid a’ phròifil agad fhoillseachadh sna h-eòlairean cho-naisgte aig Friendica cuideachd (m.e. <a href="%s">%s</a>).';
$a->strings['Allow your profile to be searchable globally?'] = 'An gabh a’ phròifil agad a lorg gu co-naisgte?';
$a->strings['Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'] = '’S urrainn dhan luchd-aithne agad postaichean a sgrìobhadh air balla do phròifile. Thèid na postaichean sin a sgaoileadh dhan luchd-aithne agad';
$a->strings['Expire starred posts'] = 'Falbhaidh an ùine air postaichean le rionnag riutha';
$a->strings['Starring posts keeps them from being expired. That behaviour is overwritten by this setting.'] = 'Nuair a bhios rionnag ri post, chan fhalbh an ùine orra. Sgrìobhaidh an roghainn seo thairis air a’ ghiùlan sin.';
$a->strings['Someone liked your content'] = '’S toigh le cuideigin an t-susbaint agad';
$a->strings['Someone shared your content'] = 'Cho-roinn cuideigin an t-susbaint agad';
$a->strings['Display the Dislike feature'] = 'Seall an gleus “Cha toigh leam seo”';
$a->strings['Display the Dislike button and dislike reactions on posts and comments.'] = 'Seall am putan “Cha toigh leam seo” agus freagairtean “Cha toigh leam seo” air postaichean is beachdan.';
$a->strings['Display the resharer'] = 'Seall cò rinn an co-roinneadh';
$a->strings['Display the first resharer as icon and text on a reshared item.'] = 'Seall a’ chiad neach a rinn co-roinneadh ’na ìomhaigheag agus teacsa air an nì a chaidh a cho-roinneadh.';
$a->strings['At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'] = 'Aig àm a’ chlàraidh agus a chùm conaltraidh eadar cunntas a’ chleachdaiche ’s an luchd-aithne aca, feumaidh an cleachdaiche ainm taisbeanaidh (ainm-pinn), ainm-cleachdaiche (far-ainm) agus seòladh puist-d a tha ag obair a thoirt seachad. Gabhaidh na h-ainmean inntrigeadh air duilleag pròifil a’ chunntais le duine sam bith a thadhlas air an duilleag, fiù mura dèid fiosrachadh eile na pròifil a shealltainn. Cha dèid an seòladh puist-d a chleachdadh ach airson brathan a chur dhan chleachdaiche mu chonaltradh agus cha dèid a shealltainn gu poblach. Tha cur a’ chunntais ri liosta nan cleachdaichean ann an eòlaire an nòid no san eòlaire cho-naisgte roghainneil agus gabhaidh sin a shuidheachadh ann an roghainnean a’ chleachdaiche; chan eil e riatanach dhan chonaltradh.';
$a->strings['Getting Started'] = 'Toiseach tòiseachaidh';
$a->strings['On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.'] = 'Air an duilleag <em>grad-tòiseachaidh</em> agad – gheibh thu facal-toisich air tabaichean na pròifile ’s an lìonraidh agad, ’s urrainn dhut dàimhean ùra a stèidheachadh is gheibh thu lorg air buidhnean ùra airson ballrachd fhaighinn annta.';
$a->strings['{0} has started following you'] = 'Tha {0} a’ leantainn ort a-nis';
$a->strings['%s liked %s\'s post'] = 'Is toigh le %s am post aig %s';
$a->strings['%s disliked %s\'s post'] = 'Cha toigh le %s am post aig %s';
$a->strings['%1$s has started following you'] = 'Tha %1$s a’ leantainn ort a-nis';
$a->strings['%1$s liked your comment on %2$s'] = '’S toigh le %1$s do bheachd air %2$s';
$a->strings['%1$s liked your post %2$s'] = 'Is toigh le %1$s am post %2$s';
$a->strings['%1$s disliked your comment on %2$s'] = 'Cha toigh le %1$s do bheachd air %2$s';
$a->strings['%1$s disliked your post %2$s'] = 'Cha toigh le %1$s am post %2$s';
$a->strings['%1$s shared your comment %2$s'] = 'Cho-roinn %1$s do bheachd %2$s';
$a->strings['%1$s shared your post %2$s'] = 'Cho-roinn %1$s am post agad %2$s';
$a->strings['%1$s shared the post %2$s from %3$s'] = 'Cho-roinn %1$s am post %2$s o %3$s';
$a->strings['%1$s shared a post from %3$s'] = 'Cho-roinn %1$s post o %3$s';
$a->strings['%1$s shared the post %2$s'] = 'Cho-roinn %1$s am post %2$s';
$a->strings['%1$s shared a post'] = 'Cho-roinn %1$s post';
$a->strings['[Friendica:Notify]'] = '[Friendica:Brath]';
$a->strings['%1$s sent you a new private message at %2$s.'] = 'Chuir %1$s teachdaireachd phrìobhaideach ùr thugad aig %2$s.';
$a->strings['a private message'] = 'teachdaireachd phrìobhaideach';
$a->strings['%1$s sent you %2$s.'] = 'Chuir %1$s %2$s thugad.';
$a->strings['Please visit %s to view and/or reply to your private messages.'] = 'Tadhail air %s a shealltainn agus/no a’ freagairt dha na teachdaireachdan prìobhaideach agad.';
$a->strings['%s commented on an item/conversation you have been following.'] = 'Chuir %s beachd ri nì/còmhradh air a bheil thu a’ leantainn.';
$a->strings['Please visit %s to view and/or reply to the conversation.'] = 'Tadhail air %s a shealltainn agus/no a’ freagairt dhan chòmhradh.';
$a->strings['%1$s posted to your profile wall at %2$s'] = 'Chuir %1$s post ri balla na pròifil agad aig %2$s';
$a->strings['%1$s posted to [url=%2$s]your wall[/url]'] = 'Chuir %1$s post ris [url=%2$s]a’ bhalla agad[/url]';
$a->strings['%s %s shared a new post'] = 'Cho-roinn %s%s post ùr';
$a->strings['Private Message'] = 'Teachdaireachd phrìobhaideach';
$a->strings['Public Message'] = 'Teachdaireachd phoblach';
$a->strings['Unlisted Message'] = 'Teachdaireachd fhalaichte o liostaichean';
$a->strings['This entry was edited'] = 'Chaidh an nì seo a dheasachadh';
$a->strings['Connector Message'] = 'Teachdaireachd ceangladair';
$a->strings['Edit'] = 'Deasaich';
$a->strings['Delete globally'] = 'Sguab às sa cho-nasgadh';
$a->strings['Remove locally'] = 'Thoir air falbh gu h-ionadail';
$a->strings['Block %s'] = 'Bac %s';
$a->strings['Save to folder'] = 'Sàbhail gu pasgan';
$a->strings['I will attend'] = 'Bidh mi an làthair';
$a->strings['I will not attend'] = 'Cha bhi mi ann';
$a->strings['I might attend'] = 'Dh’fhaoidte gum bi mi an làthair';
$a->strings['Ignore thread'] = 'Leig seachad an snàithlean';
$a->strings['Unignore thread'] = 'Na leig seachad an snàithlean tuilleadh';
$a->strings['Toggle ignore status'] = 'Toglaich staid na leigeil seachad';
$a->strings['Add star'] = 'Cuir rionnag ris';
$a->strings['Remove star'] = 'Thoir an rionnag air falbh';
$a->strings['Toggle star status'] = 'Toglaich staid na rionnaige';
$a->strings['Pin'] = 'Prìnich';
$a->strings['Unpin'] = 'Dì-phrìnich';
$a->strings['Toggle pin status'] = 'Toglaich staid a’ phrìneachaidh';
$a->strings['Pinned'] = 'Prìnichte';
$a->strings['Add tag'] = 'Cuir taga ris';
$a->strings['Quote share this'] = 'Co-roinn seo le iomradh';
$a->strings['Quote Share'] = 'Iomradh';
$a->strings['Reshare this'] = 'Co-roinn seo às ùr';
$a->strings['Reshare'] = 'Co-roinn';
$a->strings['Cancel your Reshare'] = 'Sguir dhen cho-roinneadh agad';
$a->strings['Unshare'] = 'Na co-roinn';
$a->strings['%s (Received %s)'] = '%s (air fhaighinn %s)';
$a->strings['Comment this item on your system'] = 'Thoir beachd ris an nì seo san t-siostam agad';
$a->strings['Remote comment'] = 'Beachd cèin';
$a->strings['Share via ...'] = 'Co-roinn slighe…';
$a->strings['Share via external services'] = 'Co-roinn slighe seirbheise cèine';
$a->strings['to'] = 'gu';
$a->strings['via'] = 'slighe';
$a->strings['Wall-to-Wall'] = 'Balla gu balla';
$a->strings['via Wall-To-Wall:'] = 'slighe balla bu balla:';
$a->strings['Reply to %s'] = 'Freagair gu %s';
$a->strings['More'] = 'Barrachd';
$a->strings['Quick Start'] = 'Grad-tòiseachadh';
