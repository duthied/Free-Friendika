
<h3>{{$title}}</h3>
<div id="identity-manage-desc">{{$desc}}</div>
<div id="identity-manage-choose">{{$choose}}</div>
{{*<div id="identity-selector-wrapper">
	<form action="manage" method="post" >
	<select name="identity" size="10" onchange="this.form.submit();" >

	{{foreach $identities as $id}}
		<option {{$id.selected}} value="{{$id.uid}}">{{$id.username}} ({{$id.nickname}})</option>
	{{/foreach}}

	</select>
	<div id="identity-select-break"></div>

	
	</form>
</div>*}}

<div id="identity-selector-wrapper">
	<form action="manage" method="post" >
	

	{{foreach $identities as $id}}
		<button  name="identity" value="{{$id.uid}}" onclick="this.form.submit();" class="id-selection-photo-link" title="{{$id.username}}"><img class="channel-photo" src="{{$id.thumb}}" alt="{{$id.username}}" /></button>
	{{/foreach}}



	{{*<!--<input id="identity-submit" type="submit" name="submit" value="{{$submit}}" />-->*}}
	</form>
</div>
	
	