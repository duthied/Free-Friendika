
{{include file="section_title.tpl"}}

{{foreach $entries as $entry}}
	<div class="profile-match-wrapper">
		<div class="profile-match-photo">
			<a href="{{$entry.url}}">
				<img src="{{$entry.photo}}" alt="{{$entry.name}}" width="80" height="80" title="{{$entry.name}} [{{$entry.url_clean}}]" onError="this.src='../../../images/person-48.jpg';" />
			</a>
		</div>
		<div class="profile-match-break"></div>
		<div class="profile-match-name">
			<a href="{{$entry.url}}" title="{{$entry.name}}">{{$entry.name}}</a>
		</div>
		<div class="profile-match-end"></div>
		{{if $entry.connlnk}}
		<div class="profile-match-connect"><a href="{{$entry.connlnk}}" title="{{$entry.conntxt}}">{{$entry.conntxt}}</a></div>
		{{/if}}
		<a href="{{$entry.ignlnk}}" title="{{$entry.ignore}}" class="icon drophide profile-match-ignore" {{*onmouseout="imgdull(this);" onmouseover="imgbright(this);" *}}onclick="return confirmDelete();" ></a>
	</div>
{{/foreach}}

<div class="clear"></div>
