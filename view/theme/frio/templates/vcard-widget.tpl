
<div class="vcard widget">
	{{if $url}}
		<div id="profile-photo-wrapper" class="thumbnail"><a href="{{$url}}"><img class="vcard-photo photo" src="{{$photo}}" alt="{{$name}}" /></a></div>
	{{else}}
		<div id="profile-photo-wrapper" class="thumbnail"><img class="vcard-photo photo" src="{{$photo}}" alt="{{$name}}" /></div>
	{{/if}}

	{{* The short information which will appended to the second navbar by scrollspy *}}
	<div id="vcard-short-info-wrapper" style="display: none;">
		<div id="vcard-short-info" class="media" style="display: none">
			<div id="vcard-short-photo-wrapper" class="pull-left">
				<img class="media-object" src="{{$photo}}" alt="{{$name}}" />
			</div>

			<div id="vcard-short-desc" class="media-body">
				<h4 class="media-heading">{{$name}}</h4>
				{{if $addr}}<div class="vcard-short-addr">{{$addr}}</div>{{/if}}
			</div>
		</div>
	</div>

	<div class="panel-body">
		<div class="fn">{{$name}}</div>
		{{if $addr}}<div class="p-addr">{{$addr}}</div>{{/if}}
		{{if $pdesc}}<div class="title">{{$pdesc}}</div>{{/if}}

		{{if $account_type}}<div class="account-type">{{$account_type}}</div>{{/if}}
		{{if $network_name}}<dl class="network"><dt class="network-label">{{$network}}</dt><dd class="x-network">{{$network_name}}</dd></dl>{{/if}}
		<div id="profile-vcard-break"></div>
	</div>
</div>
