
<div class="vcard h-card">

	<div class="fn p-name" dir="auto">{{$profile.name}}</div>
	
	{{if $profile.addr}}<div class="p-addr">{{$profile.addr}}</div>{{/if}}
	
	<div id="profile-photo-wrapper"><img class="photo u-photo" width="175" height="175" src="{{$profile.photo}}" alt="{{$profile.name}}"></div>

	{{if $account_type}}<div class="account-type">{{$account_type}}</div>{{/if}}

	{{if $profile.network_link}}<dl class="network"><dt class="network-label">{{$network}}</dt><dd class="x-network">{{$profile.network_link nofilter}}</dd></dl>{{/if}}

	{{if $location}}
		<dl class="location" dir="auto">
			<dt class="location-label">{{$location}}</dt>
			<dd class="adr h-adr">
				{{if $profile.address}}<p class="street-address p-street-address">{{$profile.address nofilter}}</p>{{/if}}
				{{if $profile.location}}<p class="p-location">{{$profile.location}}</p>{{/if}}
			</dd>
		</dl>
	{{/if}}

	{{if $profile.about}}<div class="title" dir="auto">{{$profile.about nofilter}}</div>{{/if}}

	{{if $profile.upubkey}}<div class="key" style="display:none;">{{$profile.upubkey}}</div>{{/if}}

	{{if $homepage}}<dl class="homepage"><dt class="homepage-label">{{$homepage}}</dt><dd class="homepage-url"><a href="{{$profile.homepage}}" class="u-url" rel="me" target="external-link">{{$profile.homepage}}</a></dd></dl>{{/if}}

	{{include file="diaspora_vcard.tpl"}}

	<div id="profile-vcard-break"></div>
	<div id="profile-extra-links">
		<ul>
			{{if $unfollow_link}}
				<li><a id="dfrn-request-link" href="{{$unfollow_link}}">{{$unfollow}}</a></li>
			{{/if}}
			{{if $follow_link}}
				<li><a id="dfrn-request-link" href="{{$follow_link}}">{{$follow}}</a></li>
			{{/if}}
			{{if $wallmessage_link}}
				<li><a id="wallmessage-link" href="{{$wallmessage_link}}">{{$wallmessage}}</a></li>
			{{/if}}
			{{if $subscribe_feed_link}}
				<li><a id="subscribe-feed-link" href="{{$subscribe_feed_link}}">{{$subscribe_feed}}</a></li>
			{{/if}}
		</ul>
	</div>
</div>

{{$contact_block nofilter}}


