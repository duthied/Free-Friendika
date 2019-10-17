
<h3>{{$title}}</h3>
<div id="identity-delegation-desc">{{$desc nofilter}}</div>
<div id="identity-delegation-choose">{{$choose}}</div>

<div id="identity-selector-wrapper" role="menu">
	<form action="delegation" method="post">

	{{foreach $identities as $identity}}
		<div class="itentity-match-wrapper {{if $identity.selected}}selected-identity{{/if}}" id="identity-match-{{$identity.uid}}">
			<div class="identity-match-photo" id="identity-match-photo-{{$identity.uid}}">
				<button type="submit" name="identity" value="{{$identity.uid}}" title="{{$identity.username}}">
					<img src="{{$identity.thumb}}" alt="{{$identity.username}}" />
					{{if $identity.notifications}}<span class="delegation-notify badge">{{$identity.notifications}}</span>{{/if}}
				</button>
			</div>

			<div class="identity-match-break"></div>

			<div class="identity-match-desc">
				<div class="identity-match-name" id="identity-match-name-{{$identity.uid}}">
					{{if $identity.selected}}
						{{$identity.username}}
					{{else}}
						<button type="submit" name="identity" class="btn-link" value="{{$identity.uid}}">{{$identity.username}}</button>
					{{/if}}
				</div>
				<div class="identity-match-details" id="identity-match-nick-{{$identity.uid}}">({{$identity.nickname}})</div>
			</div>
			<div class="identity-match-end"></div>
		</div>
	{{/foreach}}

	<div class="identity-match-break"></div>

	</form>
</div>
