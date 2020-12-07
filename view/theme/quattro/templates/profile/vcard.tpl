<div class="vcard h-card">

	<div class="tool">
		<div class="fn p-name">{{$profile.name}}</div>
		{{if $profile.edit}}
			<div class="action">
			<a class="icon s16 edit ttright" href="#" rel="#profiles-menu" title="{{$profile.edit.3}}"><span>{{$profile.edit.1}}</span></a>
			<ul id="profiles-menu" class="menu-popup">
				<li>
					<a href="{{$profile.edit.0}}">{{$profile.edit.1}}</a>
				</li>
				<li><a href="settings/profile/photo" >{{$profile.menu.chg_photo}}</a></li>
			</ul>
			</div>
		{{/if}}
	</div>

	{{if $profile.addr}}<div class="p-addr">{{$profile.addr}}</div>{{/if}}

	<div id="profile-photo-wrapper"><img class="photo u-photo" width="175" height="175" src="{{$profile.photo}}?rev={{$profile.picdate}}" alt="{{$profile.name}}" /></div>

	{{if $account_type}}<div class="account-type">{{$account_type}}</div>{{/if}}

	{{if $profile.network_link}}<dl class="network"><dt class="network-label">{{$network}}</dt><dd class="x-network">{{$profile.network_link nofilter}}</dd></dl>{{/if}}

	{{if $location}}
		<dl class="location"><dt class="location-label">{{$location}}</dt> 
		<dd class="adr h-adr">
			{{if $profile.address}}<div class="street-address p-street-address">{{$profile.address nofilter}}</div>{{/if}}
			<span class="city-state-zip">
				<span class="locality p-locality">{{$profile.locality}}</span>{{if $profile.locality}}, {{/if}}
				<span class="region p-region">{{$profile.region}}</span>
				<span class="postal-code p-postal-code">{{$profile.postal_code}}</span>
			</span>
			{{if $profile.country_name}}<span class="country-name p-country-name">{{$profile.country_name}}</span>{{/if}}
		</dd>
		</dl>
	{{/if}}

	{{if $about}}<div class="title">{{$profile.about nofilter}}</div>{{/if}}

        {{if $profile.xmpp}}
                <dl class="xmpp">
                        <dt class="xmpp-label">{{$xmpp}}</dt>
                        <dd class="xmpp-data">{{$profile.xmpp}}</dd>
                </dl>
        {{/if}}

	{{if $profile.upubkey}}<div class="key" style="display:none;">{{$profile.upubkey}}</div>{{/if}}

	{{if $homepage}}
	<dl class="homepage"><dt class="homepage-label">{{$homepage}}</dt>
		<dd class="homepage-url"><a href="{{$profile.homepage}}" class="u-url" rel="me" target="external-link">{{$profile.homepage}}</a></dd>
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


