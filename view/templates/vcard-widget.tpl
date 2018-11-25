
<div class="vcard h-card">
	<div class="fn p-name">{{$name|escape}}</div>
	{{if $addr}}<div class="p-addr">{{$addr|escape}}</div>{{/if}}
	{{if $pdesc}}<div class="title p-job-title">{{$pdesc}}</div>{{/if}}
	{{if $url}}
	<div id="profile-photo-wrapper"><a href="{{$url}}"><img class="vcard-photo photo u-photo" style="width: 175px; height: 175px;" src="{{$photo}}" alt="{{$name|escape}}" /></a></div>
	{{else}}
	<div id="profile-photo-wrapper"><img class="vcard-photo photo u-photo" style="width: 175px; height: 175px;" src="{{$photo}}" alt="{{$name|escape}}" /></div>
	{{/if}}
	{{if $account_type}}<div class="account-type">{{$account_type|escape}}</div>{{/if}}
	{{if $network_name}}<dl class="network"><dt class="network-label">{{$network}}</dt><dd class="x-network">{{$network_name}}</dd></dl>{{/if}}
	<div id="profile-vcard-break"></div>
</div>
