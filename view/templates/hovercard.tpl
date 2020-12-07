<div class="basic-content" >
	<div class="hover-card-details">
		<div class="hover-card-header left-align">
			<div class="hover-card-pic left-align">
				<span class="image-wrapper medium">
					<a href="{{$profile.url}}" title="{{$profile.name}}"><img href="" class="left-align thumbnail" src="{{$profile.thumb}}" alt="{{$profile.name}}"></a>
				</span>
			</div>
			<div class="hover-card-content">
				<div class="profile-entry-name">
					<h4 class="left-align1"><a href="{{$profile.url}}">{{$profile.name}}</a></h4>{{if $profile.account_type}}<span>{{$profile.account_type}}</span>{{/if}}
				</div>
				<div class="profile-details">
					<span class="profile-addr">{{$profile.addr}}</span>
					{{if $profile.network_link}}<span class="profile-network">({{$profile.network_link nofilter}})</span>{{/if}}
				</div>
				{{*{{if $profile.about}}<div class="profile-details profile-about">{{$profile.about nofilter}}</div>{{/if}}*}}

			</div>
			<div class="hover-card-actions  right-aligned">
				{{* here are the differnt actions like privat message, poke, delete and so on *}}
				{{* @todo we have two different photo menus one for contacts and one for items at the network stream. We currently use the contact photo menu, so the items options are missing We need to move them *}}
				<div class="hover-card-actions-social">
					{{if $profile.actions.pm}}<a class="btn btn-labeled btn-primary btn-sm add-to-modal" href="{{$profile.actions.pm.1}}" aria-label="{{$profile.actions.pm.0}}"><i class="fa fa-envelope" aria-hidden="true" title="{{$profile.actions.pm.0}}"></i><span class="sr-only">{{$profile.actions.pm.0}}</span></a>{{/if}}
					{{if $profile.actions.poke}}<a class="btn btn-labeled btn-primary btn-sm add-to-modal" href="{{$profile.actions.poke.1}}" aria-label="{{$profile.actions.poke.0}}"><i class="fa fa-heartbeat" aria-hidden="true" title="{{$profile.actions.poke.0}}"></i><span class="sr-only">{{$profile.actions.poke.0}}</span></a>{{/if}}
				</div>
				<div class="hover-card-actions-connection">
					{{if $profile.actions.network}}<a class="btn btn-labeled btn-primary btn-sm" href="{{$profile.actions.network.1}}" aria-label="{{$profile.actions.network.0}}" title="{{$profile.actions.network.0}}"><i class="fa fa-cloud" aria-hidden="true"></i></a>{{/if}}
					{{if $profile.actions.edit}}<a class="btn btn-labeled btn-primary btn-sm" href="{{$profile.actions.edit.1}}" aria-label="{{$profile.actions.edit.0}}" title="{{$profile.actions.edit.0}}"><i class="fa fa-user" aria-hidden="true"></i></a>{{/if}}
					{{if $profile.actions.follow}}<a class="btn btn-labeled btn-primary btn-sm" href="{{$profile.actions.follow.1}}" aria-label="{{$profile.actions.follow.0}}" title="{{$profile.actions.follow.0}}"><i class="fa fa-user-plus" aria-hidden="true"></i></a>{{/if}}
					{{if $profile.actions.unfollow}}<a class="btn btn-labeled btn-primary btn-sm" href="{{$profile.actions.unfollow.1}}" aria-label="{{$profile.actions.unfollow.0}}" title="{{$profile.actions.unfollow.0}}"><i class="fa fa-user-times" aria-hidden="true"></i></a>{{/if}}
				</div>
			</div>
		</div>

		<div class="clearfix"></div>

	</div>
</div>
{{if $profile.tags}}<div class="hover-card-footer">{{$profile.tags}}</div>{{/if}}
