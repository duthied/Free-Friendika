<div class="vcard h-card widget">

	<div id="profile-photo-wrapper">
		{{if $url}}
		<a href="{{$url}}"><img class="photo u-photo" src="{{$photo}}" alt="{{$name}}" /></a>
		{{else}}
		<img class="photo u-photo" src="{{$photo}}" alt="{{$name}}" />
		{{/if}}
	</div>

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
		<div class="profile-header">
			<h3 class="fn p-name">{{$name}}</h3>

			{{if $addr}}<div class="p-addr">{{$addr}}</div>{{/if}}

			{{if $account_type}}<div class="account-type">({{$account_type}})</div>{{/if}}

			{{if $pdesc}}<div class="title">{{$pdesc}}</div>{{/if}}

			{{if $network_link}}<dl class="network"><dt class="network-label">{{$network}}</dt><dd class="x-network">{{$network_link nofilter}}</dd></dl>{{/if}}
		</div>

		<div id="profile-extra-links">
			<div id="dfrn-request-link-button">
				{{if $follow_link}}
					<a id="dfrn-request-link" class="btn btn-labeled btn-primary btn-sm" href="{{$follow_link}}"">
						<span class=""><i class="fa fa-user-plus"></i></span>
						<span class="">{{$follow}}</span>
					</a>
				{{/if}}
				{{if $unfollow_link}}
					<a id="dfrn-request-link" class="btn btn-labeled btn-primary btn-sm" href="{{$unfollow_link}}">
						<span class=""><i class="fa fa-user-times"></i></span>
						<span class="">{{$unfollow}}</span>
					</a>
				{{/if}}
			</div>
			{{if $wallmessage_link}}
				<div id="wallmessage-link-botton">
					<button type="button" id="wallmessage-link" class="btn btn-labeled btn-primary btn-sm" onclick="openWallMessage('{{$wallmessage_link}}')">
						<span class=""><i class="fa fa-envelope"></i></span>
						<span class="">{{$wallmessage}}</span>
					</button>
				</div>
			{{/if}}
		</div>
	</div>
</div>
