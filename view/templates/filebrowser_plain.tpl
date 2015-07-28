	<!--
		This is the template used by mod/fbrowser.php when is called from plain text editor.
		See also 'filebrowser.tpl'
	-->

<script type="text/javascript" src="{{$baseurl}}/js/ajaxupload.js" ></script>

<div class="fbrowser {{$type}}">

	<div class="path">
		{{foreach $path as $p}}<a href="{{$p.0}}">{{$p.1}}</a>{{/foreach}}
	</div>
	
	<div class="folders">
		<ul>
			{{foreach $folders as $f}}<li><a href="{{$baseurl}}/fbrowser/{{$type}}/{{$f.0}}">{{$f.1}}</a></li>{{/foreach}}
		</ul>
	</div>
		
	{{foreach $files as $f}}
	<div class="photo-album-image-wrapper">
		<a href="#" class="photo-album-photo-link" data-link="{{$f.0}}" data-filename="{{$f.1}}" data-img="{{$f.2}}">
			<img src="{{$f.2}}">
			<p>{{$f.1}}</p>
		</a>
	</div>
	<div class="photo-album-image-wrapper-end"></div>
	{{/foreach}}

	<div class="upload">
		<button id="upload-{{$type}}"><img id="profile-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait|escape:'html'}}" style="display: none;" /> {{"Upload"|t}}</button> 
	</dksiv>
</div>



	<script>
		$(".photo-album-photo-link").on('click', function(e){
			e.preventDefault();
			
			{{if $type == "image"}}
			var embed = "[url="+this.dataset.link+"][img]"+this.dataset.img+"[/img][/url]";
			{{/if}}
			{{if $type == "file"}}
			var embed = "[url="+this.dataset.link+"][img]"+this.dataset.img+"[/img] "+this.dataset.filename+"[/url]";
			{{/if}}
			console.log(this.dataset.filename, embed, parent.$("body"));
			parent.$("body").trigger("fbrowser.{{$type}}", [
				this.dataset.filename,
				embed,
			]);
			
		});
		
		if ($("#upload-image").length)
			var image_uploader = new window.AjaxUpload(
				'upload-image',
				{ action: 'wall_upload/{{$nickname}}',
					name: 'userfile',
					onSubmit: function(file,ext) { $('#profile-rotator').show(); },
					onComplete: function(file,response) {
						location = baseurl + "/fbrowser/image/?mode=minimal";
						location.reload(true);
					}				 
				}
			);

		if ($("#upload-file").length)
			var file_uploader = new window.AjaxUpload(
				'upload-file',
				{ action: 'wall_attach/{{$nickname}}',
					name: 'userfile',
					onSubmit: function(file,ext) { $('#profile-rotator').show(); },
					onComplete: function(file,response) {
						location = baseurl + "/fbrowser/file/?mode=minimal";
						location.reload(true);				}				 
				}
			);
		
		

	</script>

	</body>
	
</html>
