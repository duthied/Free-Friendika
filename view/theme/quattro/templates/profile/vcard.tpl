<div class="vcard h-card">

	<div class="tool">
		<div class="fn p-name" dir="auto">{{$profile.name}}</div>
		{{if $profile.edit}}
			<div class="action">
			<a class="icon s16 edit ttright" href="#" rel="#profiles-menu" title="{{$profile.edit.3}}"><span>{{$profile.edit.1}}</span></a>
			<ul id="profiles-menu" class="menu-popup">
				<li>
					<a href="{{$profile.edit.0}}">{{$profile.edit.1}}</a>
				</li>
				<li><a href="settings/profile/photo">{{$profile.menu.chg_photo}}</a></li>
			</ul>
			</div>
		{{/if}}
	</div>

	{{if $profile.addr}}<div class="p-addr">{{$profile.addr}}</div>{{/if}}

	<div id="profile-photo-wrapper"><img class="photo u-photo" width="175" height="175" src="{{$profile.photo}}" alt="{{$profile.name}}" /></div>

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

	{{if $about}}<div class="title" dir="auto">{{$profile.about nofilter}}</div>{{/if}}

    {{if $profile.xmpp}}
	<dl class="xmpp">
        <dt class="xmpp-label">{{$xmpp}}</dt>
        <dd class="xmpp-data">{{$profile.xmpp}}</dd>
	</dl>
    {{/if}}

    {{if $profile.matrix}}
	<dl class="matrix">
        <dt class="matrix-label">{{$matrix}}</dt>
        <dd class="matrix-data">{{$profile.matrix}}</dd>
	</dl>
    {{/if}}

	{{if $profile.upubkey}}<div class="key" style="display:none;">{{$profile.upubkey}}</div>{{/if}}

	{{if $homepage}}
	<dl class="homepage"><dt class="homepage-label">{{$homepage}}</dt>
		<dd class="homepage-url"><a href="{{$profile.homepage}}" class="u-url" rel="me" target="external-link">{{$profile.homepage}}</a>{{if $profile.homepage_verified}} <span title="{{$homepage_verified}}">✔</span>{{/if}}</dd>
	</dl>
	{{/if}}

	{{include file="diaspora_vcard.tpl"}}

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


