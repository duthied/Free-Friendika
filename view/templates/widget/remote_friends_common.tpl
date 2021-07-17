<div id="remote-friends-in-common" class="bigwidget">
	<div id="rfic-desc">{{$desc nofilter}} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{if $linkmore}}<a href="profile/{{$nickname}}/contacts/common">{{$more}}</a>{{/if}}</div>
	{{foreach $contacts as $contact}}
	<div class="profile-match-wrapper">
		<div class="profile-match-photo">
			<a href="{{$contact.url}}">
				<img src="{{$contact.photo}}" width="80" height="80" alt="{{$contact.name}}" title="{{$contact.name}}" />
			</a>
		</div>
		<div class="profile-match-break"></div>
		<div class="profile-match-name">
			<a href="{{$contact.url}}" title="{{$contact.name}}">{{$contact.name}}</a>
		</div>
		<div class="profile-match-end"></div>
	</div>
	{{/foreach}}
	<div id="rfic-end" class="clear"></div>
</div>
