{{if $diaspora}}
<div style="display:none;">
	<dl class="entity_uid">
		<dt>Uid</dt>
		<dd>
			<span class="uid">{{$diaspora.guid}}</span>
		</dd>
	</dl>
	<dl class='entity_nickname'>
		<dt>Nickname</dt>
		<dd>		
			<span class="nickname">{{$diaspora.nickname}}</span>
		</dd>
	</dl>
	<dl class='entity_full_name'>
		<dt>Full_name</dt>
		<dd>
			<span class='fn'>{{$diaspora.fullname}}</span>
		</dd>
	</dl>
	<dl class="entity_searchable">
		<dt>Searchable</dt>
		<dd>
			<span class="searchable">{{$diaspora.searchable}}</span>
		</dd>
	</dl>
	<dl class='entity_first_name'>
		<dt>First_name</dt>
		<dd>
		<span class='given_name'>{{$diaspora.firstname}}</span>
		</dd>
	</dl>
	<dl class='entity_family_name'>
		<dt>Family_name</dt>
		<dd>
		<span class='family_name'>{{$diaspora.lastname}}</span>
		</dd>
	</dl>
	<dl class="entity_url">
		<dt>Url</dt>
		<dd>
			<a id="pod_location" class="url" rel="me" href="{{$diaspora.podloc}}/">{{$diaspora.podloc}}/</a>
		</dd>
	</dl>
	<dl class="entity_photo">
		<dt>Photo</dt>
		<dd>
			<img class="photo avatar" height="300" width="300" src="{{$diaspora.photo300}}">
		</dd>
	</dl>
	<dl class="entity_photo_medium">
		<dt>Photo_medium</dt>
		<dd> 
			<img class="photo avatar" height="100" width="100" src="{{$diaspora.photo100}}">
		</dd>
	</dl>
	<dl class="entity_photo_small">
		<dt>Photo_small</dt>
		<dd>
			<img class="photo avatar" height="50" width="50" src="{{$diaspora.photo50}}">
		</dd>
	</dl>
</div>
{{/if}}
