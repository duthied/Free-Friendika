<div class="shared-wrapper well well-sm">
	<div class="shared_header">
		{{if $avatar}}
			<a href="{{$profile}}" target="_blank" rel="noopener noreferrer" class="avatar shared-userinfo">
				<img src="{{$avatar}}" alt="">
			</a>
		{{/if}}
		<div class="metadata">
			<p class="shared-author">
				<a href="{{$profile}}" target="_blank" rel="noopener noreferrer" class="shared-wall-item-name">
					{{$author}}
				</a>
			</p>
			<p class="shared-wall-item-ago">
				{{if $guid}}
				<a href="/display/{{$guid}}">
					{{/if}}
					<span class="shared-time">{{$posted}}</span>
					{{if $guid}}
				</a>
				{{/if}}
			</p>
		</div>
		<div class="preferences">
			{{if $network_icon && $link}}
   				<span class="wall-item-network"><a href="{{$link}}" class="plink u-url" aria-label="{{$link_title}}" target="_blank"><i class="fa fa-{{$network_icon}} fakelink" title="{{$network_name}} - {{$link_title}}" aria-hidden="true"></i></a></span>
			{{elseif $link}}
       				<a href="{{$link}}" class="plink u-url" aria-label="{{$link_title}}" title="{{$network_name}} - {{$link_title}}" target="_blank">{{$network_name}}</a>
   			{{elseif $network_icon}}
       				<span class="wall-item-network"><i class="fa fa-{{$network_icon}}" title="{{$network_name}}" aria-hidden="true"></i></span>
    			{{else}}
        			<span class="wall-item-network" title="{{$app}}">{{$network_name}}</span>
    			{{/if}}
		</div>
	</div>
	<blockquote class="shared_content" dir="auto">{{$content nofilter}}</blockquote>
</div>
