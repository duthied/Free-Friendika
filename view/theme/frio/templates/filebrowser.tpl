	<!--
		This is the template used by mod/fbrowser.php
	-->
<style>
	#buglink_wrapper{display:none;} /* hide buglink. only in this page */
</style>
{{*<script type="text/javascript" src="{{$baseurl}}/js/ajaxupload.js" ></script>*}}
{{*<script type="text/javascript" src="view/theme/frio/js/filebrowser.js"></script>*}}

<div class="fbrowser {{$type}}">
	<div class="fbrowser-content">
		<input id="fb-nickname" type="hidden" name="type" value="{{$nickname}}" />
		<input id="fb-type" type="hidden" name="type" value="{{$type}}" />

        <div class="error hidden">
            <span></span> <button type="button" class="btn btn-link" class="close">X</a>
        </div>

        <div class="path">
            {{foreach $path as $p}}<button type="button" class="btn-link" data-folder="{{$p.0}}">{{$p.1}}</button>{{/foreach}}
        </div>

        {{if $folders }}
        <div class="folders">
            <ul>
                {{foreach $folders as $f}}<li><button type="button" class="btn-link" data-folder="{{$f.0}}">{{$f.1}}</button></li>{{/foreach}}
            </ul>
        </div>
        {{/if}}

        <div class="list">
            {{foreach $files as $f}}
            <div class="photo-album-image-wrapper">
                <button type="button" class="btn btn-link photo-album-photo-link" data-link="{{$f.0}}" data-filename="{{$f.1}}" data-img="{{$f.2}}">
                    <img src="{{$f.2}}">
                    <p>{{$f.1}}</p>
                </button>
			</div>
			{{/foreach}}
		</div>

		<div class="upload">
			<button id="upload-{{$type}}"><img id="profile-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait|escape:'html'}}" style="display: none;" /> {{"Upload"|t}}</button>
		</div>
	</div>
	<div class="profile-rotator-wrapper" style="display: none;">
		<i class="fa fa-circle-o-notch fa-spin"></i>
	</div>
</div>
