<div class="shared-wrapper">
	<div class="shared_header">
		{{if $avatar}}
			<a href="{{$profile}}" target="_blank" rel="noopener noreferrer" class="shared-userinfo">
			<img src="{{$avatar}}" height="32" width="32">
			</a>
		{{/if}}
		<div><a href="{{$profile}}" target="_blank" rel="noopener noreferrer" class="shared-wall-item-name"><span class="shared-author">{{$author}}</span></a></div>
		<div class="shared-wall-item-ago"><small><a href="{{$link}}" target="_blank" rel="noopener noreferrer"><span class="shared-time">{{$posted}}</a></a></small></div>
	</div>
	<blockquote class="shared_content">{{$content nofilter}}</blockquote>
</div>
