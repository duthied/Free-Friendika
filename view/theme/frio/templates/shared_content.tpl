<div class="shared-wrapper well well-sm">
	<div class="shared_header">
		{{if $avatar}}
			<a href="{{$profile}}" target="_blank" rel="noopener noreferrer" class="shared-userinfo">
				<img src="{{$avatar}}" height="32" width="32">
			</a>
		{{/if}}
		<div><a href="{{$profile}}" target="_blank" rel="noopener noreferrer" class="shared-wall-item-name"><span class="shared-author">{{$author}}</span></a></div>
		<div class="preferences">
			{{if $network_icon}}
				<span class="wall-item-network"><i class="fa fa-{{$network_icon}}" title="{{$network_name}}" aria-hidden="true"></i></span>
			{{else}}
				<span class="wall-item-network">{{$network_name}}</span>
			{{/if}}
			{{if $link}}
				<a href="{{$link}}" class="plink u-url" aria-label="{{$link_title}}" title="{{$link_title}}">
					<i class="fa fa-external-link"></i>
				</a>
			{{/if}}
		</div>
		<div class="shared-wall-item-ago"><small><span class="shared-time">{{$posted}}</span></small></div>
	</div>
	<blockquote class="shared_content">{{$content nofilter}}</blockquote>
</div>
