Dropzone.autoDiscover = false;
var DzFactory = function (max_imagesize) {
	this.createDropzone = function(dropSelector, textareaElementId) {
		return new Dropzone(dropSelector, {
			paramName: 'userfile', // The name that will be used to transfer the file
			maxFilesize: max_imagesize, // MB
			url: '/media/photo/upload?album=',
			acceptedFiles: 'image/*',
			clickable: true,
			dictDefaultMessage: dzStrings.dictDefaultMessage,
			dictFallbackMessage: dzStrings.dictFallbackMessage,
			dictFallbackText: dzStrings.dictFallbackText,
			dictFileTooBig: dzStrings.dictFileTooBig,
			dictInvalidFileType: dzStrings.dictInvalidFileType,
			dictResponseError: dzStrings.dictResponseError,
			dictCancelUpload: dzStrings.dictCancelUpload,
			dictUploadCanceled: dzStrings.dictUploadCanceled,
			dictCancelUploadConfirmation: dzStrings.dictCancelUploadConfirmation,
			dictRemoveFile: dzStrings.dictRemoveFile,
			dictMaxFilesExceeded: dzStrings.dictMaxFilesExceeded,
			accept: function(file, done) {
				done();
			},
			init: function() {
				this.on('success', function(file, serverResponse) {
					const targetTextarea = document.getElementById(textareaElementId);
					if (targetTextarea.setRangeText) {
						//if setRangeText function is supported by current browser
						targetTextarea.setRangeText(serverResponse);
					} else {
						targetTextarea.focus();
						document.execCommand('insertText', false /*no UI*/, serverResponse);
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
		const self = this;
		var dropzone = this.createDropzone(dropSelector, textareaElementId);
		$(dropSelector).on('paste', function(event) {
			self.copyPaste(event, dropzone);
		})
	};
}

