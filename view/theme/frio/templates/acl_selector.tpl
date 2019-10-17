
<div id="acl-wrapper">
	<div class="form-group form-group-search">
		<button id="acl-showall" class="btn btn-block btn-default"><i class="fa fa-globe"></i> {{$showall}}</button>
	</div>
	<div class="form-group form-group-search">
		<input type="text" id="acl-search" class="form-control form-search" autocomplete="off">
	</div>
	<div id="acl-list">
		<div id="acl-list-content"></div>
	</div>
	<span id="acl-fields"></span>
</div>

<div class="acl-list-item" rel="acl-template" style="display:none">
	<img data-src="{0}" alt="{1}"><p>{1}</p>
	<button class='acl-button-hide btn btn-sm btn-default'>{{$hide}}</button>
	<button class='acl-button-show btn btn-sm btn-default'>{{$show}}</button>
</div>

{{if $networks}}
<hr style="clear:both"/>
<div class="form-group">
	<label for="profile-jot-email" id="profile-jot-email-label">{{$emailcc}}</label>
	<input type="text" name="emailcc" id="profile-jot-email" class="form-control" title="{{$emtitle}}" />
</div>
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

<script type="text/javascript">
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
