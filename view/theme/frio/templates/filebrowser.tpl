<!--
	This is the template used by mod/fbrowser.php
-->
<div id="filebrowser" class="fbrowser {{$type}}" data-nickname="{{$nickname}}" data-type="{{$type}}">
	<div class="fbrowser-content">
		<div class="error hidden">
			<span></span> <button type="button" class="btn btn-link close" aria-label="Close">X</button>
		</div>

		{{* The breadcrumb navigation *}}
		<ol class="path breadcrumb" aria-label="Breadcrumb" role="navigation">
		{{foreach $path as $folder => $name}}
			<li role="presentation"><a href="#" data-folder="{{$folder}}">{{$name}}</a></li>
		{{/foreach}}

			{{* Switch between image and file mode *}}
			<div class="fbswitcher btn-group btn-group-xs pull-right" aria-label="Switch between image and file mode">
				<button type="button" class="btn btn-default" data-mode="image" aria-label="Image Mode"><i class="fa fa-picture-o" aria-hidden="true"></i></button>
				<button type="button" class="btn btn-default" data-mode="file" aria-label="File Mode"><i class="fa fa-file-o" aria-hidden="true"></i></button>
			</div>
		</ol>

		<div class="media">

			{{* List of photo albums *}}
			{{if $folders }}
			<div class="folders media-left" role="navigation" aria-label="Album Navigation">
				<ul role="menu">
					{{foreach $folders as $folder}}
					<li role="presentation">
						<a href="#" data-folder="{{$folder}}" role="menuitem">{{$folder}}</a>
					</li>
					{{/foreach}}
				</ul>
			</div>
			{{/if}}

			{{* The main content (images or files) *}}
			<div class="list {{$type}} media-body" role="main" aria-label="Browser Content">
				<div class="fbrowser-content-container">
					{{foreach $files as $f}}
					<div class="photo-album-image-wrapper">
						<a href="#" class="photo-album-photo-link" data-link="{{$f.0}}" data-filename="{{$f.1}}" data-img="{{$f.2}}">
							<img src="{{$f.2}}" alt="{{$f.1}}">
							<p>{{$f.1}}</p>
						</a>
					</div>
					{{/foreach}}
				</div>
			</div>
		</div>

		<div class="upload">
			<button id="upload-{{$type}}">{{$upload}}</button>
		</div>
	</div>

	{{* This part contains the conent loader icon which is visible when new conent is loaded *}}
	<div class="profile-rotator-wrapper" aria-hidden="true" style="display: none;">
		<i class="fa fa-circle-o-notch fa-spin" aria-hidden="true"></i>
	</div>
</div>
