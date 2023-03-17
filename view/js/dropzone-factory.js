var DzFactory = function () {
	this.createDropzone = function(dropSelector, textareaElementId) {
		return new Dropzone(dropSelector, {
			paramName: 'userfile', // The name that will be used to transfer the file
			maxFilesize: max_imagesize, // MB
			url: '/media/photo/upload?album=',
			addRemoveLinks: true,
			acceptedFiles: 'image/*',
			clickable: true,
			accept: function(file, done) {
				done();
			},
			init: function() {
				this.on('success', function(file, serverResponse) {
					const targetTextarea = document.getElementById(textareaElementId);
					const bbcodeString = serverResponse;
					if (targetTextarea.setRangeText) {
						//if setRangeText function is supported by current browser
						targetTextarea.setRangeText(' ' + $.trim(bbcodeString) + ' ');
					} else {
						targetTextarea.focus();
						document.execCommand('insertText', false /*no UI*/, '\n' + $.trim(bbcodeString) + '\n');
					}
				});
				this.on('complete', function(file) {
					const dz = this;
					// Remove just uploaded file from dropzone, makes interface more clear.
					// Image can be seen in posting-preview
					// We need preview to get optical feedback about upload-progress.
					// you see success, when the bb-code link for image is inserted
					setTimeout(function(){
						dz.removeFile(file);
					},5000);
				});
			},
			paste: function(event){
				const items = (event.clipboardData || event.originalEvent.clipboardData).items;
				items.forEach((item) => {
					if (item.kind === 'file') {
						// adds the file to your dropzone instance
						dz.addFile(item.getAsFile());
					}
				})
			},
		});
	};

	this.copyPaste = function(event, dz) {
		const items = (event.clipboardData || event.originalEvent.clipboardData).items;
		items.forEach((item) => {
			if (item.kind === 'file') {
				// adds the file to your dropzone instance
				dz.addFile(item.getAsFile());
			}
		})
	};

	this.setupDropzone = function(dropSelector, textareaElementId) {
		var dropzone = this.createDropzone(dropSelector, textareaElementId);
		$(dropSelector).on('paste', function(event) {
			dzFactory.copyPaste(event, dropzone);
		})
	};
}

