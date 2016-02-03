
<div id="acl-wrapper">
	<input id="acl-search">
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
<div id="profile-jot-email-label">{{$emailcc}}</div><input type="text" name="emailcc" id="profile-jot-email" title="{{$emtitle|escape:'html'}}" />
<div id="profile-jot-email-end"></div>
{{if $jotnets}}
{{$jotnets}}
{{/if}}{{/if}}

<script>
$(document).ready(function() {
	if(typeof acl=="undefined"){
		acl = new ACL(
			baseurl+"/acl",
			[ {{$allowcid}},{{$allowgid}},{{$denycid}},{{$denygid}} ],
			{{$features.aclautomention}},
			{{$APP->is_mobile}}
		);
	}
});
</script>
