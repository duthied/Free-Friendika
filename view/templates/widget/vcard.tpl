
<div class="vcard h-card">
	<div class="fn p-name">{{$name}}</div>
	{{if $addr}}<div class="p-addr">{{$addr}}</div>{{/if}}
	{{if $about}}<div class="title p-job-title">{{$about nofilter}}</div>{{/if}}
	{{if $url}}
	<div id="profile-photo-wrapper"><a href="{{$url}}"><img class="vcard-photo photo u-photo" style="width: 175px; height: 175px;" src="{{$photo}}" alt="{{$name}}" /></a></div>
	{{else}}
	<div id="profile-photo-wrapper"><img class="vcard-photo photo u-photo" style="width: 175px; height: 175px;" src="{{$photo}}" alt="{{$name}}" /></div>
	{{/if}}
	{{if $account_type}}<div class="account-type">{{$account_type}}</div>{{/if}}
	{{if $network_link}}<dl class="network"><dt class="network-label">{{$network}}</dt><dd class="x-network">{{$network_link nofilter}}</dd></dl>{{/if}}

	<div id="profile-extra-links">
		<ul>
			{{if $follow_link}}
				<li><a id="dfrn-request-link" href="{{$follow_link}}">{{$follow}}</a></li>
			{{/if}}
			{{if $unfollow_link}}
				<li><a id="dfrn-request-link" href="{{$unfollow_link}}">{{$unfollow}}</a></li>
			{{/if}}
			{{if $wallmessage_link}}
				<li><a id="wallmessage-link" href="{{$wallmessage_link}}">{{$wallmessage}}</a></li>
			{{/if}}
		</ul>
	</div>

	<div id="profile-vcard-break"></div>
</div>
