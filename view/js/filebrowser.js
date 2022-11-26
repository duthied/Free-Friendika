// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
/**
 * Filebrowser - Friendica Communications Server
 *
 * Copyright (c) 2010-2021, the Friendica project
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This code handle user interaction for image/file upload/browser dialog.
 * Is loaded from filebrowser_plain.tpl
 *
 * To load filebrowser in colorbox, call
 *
 *      Dialog.doImageBrowser(eventname, id);
 *
 * or
 *
 *      Dialog.doFileBrowser(eventname, id);
 *
 * where:
 *
 * 		eventname: event name to catch return value
 * 		id: id returned to event handler
 *
 * When user select an item, an event in fired in parent page, on body element
 * The event is named
 *
 * 		fbrowser.<type>.[<eventname>]
 *
 * <type> will be one of "image" or "file", and the event handler will
 * get the following params:
 *
 * 		filename: filename of item chosen by user
 * 		embed: bbcode to embed element into posts
 * 		id: id from caller code
 *
 * example:
 *
 * 		// open dialog for select an image for a textarea with id "myeditor"
 * 		var id="myeditor";
 * 		Dialog.doImageBrowser("example", id);
 *
 *		// setup event handler to get user selection
 *		$("body").on("fbrowser.image.example", function(event, filename, bbcode, id) {
 *			// close colorbox
 *			$.colorbox.close();
 *			// replace textarea text with bbcode
 *			$(id).value = bbcode;
 *		});
 **/
const FileBrowser = {
	nickname: '',
	type: '',
	event: '',
	id: null,

	init: function (nickname, type) {
		FileBrowser.nickname = nickname;
		FileBrowser.type = type;
		FileBrowser.event = 'fbrowser.' + type;
		if (location['hash'] !== '') {
			const h = location['hash'].replace('#', '');
			FileBrowser.event = FileBrowser.event + '.' + h.split('-')[0];
			FileBrowser.id = h.split('-')[1];
		}

		console.log('FileBrowser:', nickname, type, FileBrowser.event, FileBrowser.id);

		$('.error a.close').on('click', function (e) {
			e.preventDefault();
			$('.error').addClass('hidden');
		});

		$('.folders a, .path a').on('click', function (e) {
			e.preventDefault();
			location.href = FileBrowser._getUrl("minimal", location['hash'], this.dataset.folder);
			location.reload();
		});

		$(".photo-album-photo-link").on('click', function (e) {
			e.preventDefault();

			let embed = '';
			if (FileBrowser.type === "photos") {
				embed = '[url=' + this.dataset.link + '][img=' + this.dataset.img + ']' + this.dataset.alt + '[/img][/url]';
			}
			if (FileBrowser.type === "attachment") {
				embed = '[attachment]' + this.dataset.link + '[/attachment]';
			}
			console.log(FileBrowser.event, this.dataset.filename, embed, FileBrowser.id);
			parent.$('body').trigger(FileBrowser.event, [
				this.dataset.filename,
				embed,
				FileBrowser.id
			]);

		});

		if ($('#upload-photos').length)
		{
			new window.AjaxUpload(
				'upload-photos',
				{
					action: 'profile/' + FileBrowser.nickname + '/photos/upload?response=json',
					name: 'userfile',
					responseType: 'json',
					onSubmit: function (file, ext) {
						$('#profile-rotator').show();
						$('.error').addClass('hidden');
					},
					onComplete: function (file, response) {
						if (response['error'] !== undefined) {
							$('.error span').html(response['error']);
							$('.error').removeClass('hidden');
							$('#profile-rotator').hide();
							return;
						}
						location.href = FileBrowser._getUrl("minimal", location['hash']);
						location.reload();
					}
				}
			);
		}

		if ($('#upload-attachment').length)
		{
			new window.AjaxUpload(
				'upload-attachment',
				{
					action: 'profile/' + FileBrowser.nickname + '/attachment/upload?response=json',
					name: 'userfile',
					responseType: 'json',
					onSubmit: function (file, ext) {
						$('#profile-rotator').show();
						$('.error').addClass('hidden');
					},
					onComplete: function (file, response) {
						if (response['error'] !== undefined) {
							$('.error span').html(response['error']);
							$('.error').removeClass('hidden');
							$('#profile-rotator').hide();
							return;
						}
						location.href = FileBrowser._getUrl("minimal", location['hash']);
						location.reload();
					}
				}
				);
			}
	},

	_getUrl: function (mode, hash, folder) {
		let folderValue = folder !== undefined ? folder : FileBrowser.folder;
		let folderUrl = folderValue !== undefined ? '/' + encodeURIComponent(folderValue) : '';
		return 'profile/' + FileBrowser.nickname + '/' + FileBrowser.type + '/browser' + folderUrl + '?mode=' + mode + hash;
	}
};
// @license-end
