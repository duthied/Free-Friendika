var DzFactory = function () {
	this.createDropzone = function(element, target, maxImagesize) {
		return new Dropzone( element, {
			paramName: 'userfile', // The name that will be used to transfer the file
			maxFilesize: maxImagesize, // MB
			url: '/media/photo/upload?response=url&album=',
			accept: function(file, done) {
				done();
			},
			init: function() {
				this.on('success', function(file, serverResponse) {
					var _target = $(target)
					var resp = $(serverResponse).find('div#content').text()
					if (_target.setRangeText) {
						//if setRangeText function is supported by current browser
						_target.setRangeText(' ' + $.trim(resp) + ' ')
					} else {
						_target.focus()
						document.execCommand('insertText', false /*no UI*/, ' ' + $.trim(resp) + ' ');
					}
				});
				this.on('complete', function(file) {
					var dz = this;
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
						dz.addFile(item.getAsFile())
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
				dz.addFile(item.getAsFile())
			}
		})
	};

	this.setupDropzone = function(element, target, maxImagesize) {
		var dropzone = this.createDropzone(element, target, maxImagesize)
		$(element).on('paste', function(event) {

			dzFactory.copyPaste(event, dropzone);
		})
	};
}

