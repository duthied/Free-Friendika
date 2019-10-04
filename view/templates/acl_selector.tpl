
<div id="acl-wrapper">
	<input id="acl-search" autocomplete="off">
	<a id="acl-showall">{{$showall}}</a>
	<div id="acl-list">
		<div id="acl-list-content">
		</div>
	</div>
	<span id="acl-fields"></span>
</div>

<div class="acl-list-item" rel="acl-template" style="display:none">
	<img data-src="{0}"><p>{1}</p>
	<a class='acl-button-show'>{{$show}}</a>
	<a class='acl-button-hide'>{{$hide}}</a>
</div>

{{if $networks}}
<hr style="clear:both"/>
<div id="profile-jot-email-label">{{$emailcc}}</div><input type="text" name="emailcc" id="profile-jot-email" title="{{$emtitle}}" />
<div id="profile-jot-email-end"></div>

	{{if $jotnets_fields}}
		{{if $jotnets_fields|count < 3}}
<div class="profile-jot-net">
		{{else}}
<details class="profile-jot-net">
	<summary>{{$jotnets_summary}}</summary>
		{{/if}}

		{{foreach $jotnets_fields as $jotnets_field}}
			{{if $jotnets_field.type == 'checkbox'}}
				{{include file="field_checkbox.tpl" field=$jotnets_field.field}}
			{{elseif $jotnets_field.type == 'select'}}
				{{include file="field_select.tpl" field=$jotnets_field.field}}
			{{/if}}
		{{/foreach}}

		{{if $jotnets_fields|count >= 3}}
</details>
		{{else}}
</div>
		{{/if}}
	{{/if}}
{{/if}}

<script>
$(document).ready(function() {
	if(typeof acl=="undefined"){
		acl = new ACL(
			baseurl + '/search/acl',
			[ {{$allowcid nofilter}},{{$allowgid nofilter}},{{$denycid nofilter}},{{$denygid nofilter}} ],
			{{$features.aclautomention}},
			{{if $APP->is_mobile}}true{{else}}false{{/if}}
		);
	}
});
</script>
