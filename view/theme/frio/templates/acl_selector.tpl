
<div id="acl-wrapper">
	<div class="form-group form-group-search">
		<button id="acl-showall" class="btn btn-block btn-default"><i class="fa fa-globe"></i> {{$showall}}</button>
	</div>
	<div class="form-group form-group-search">
		<input type="text" id="acl-search" class="form-control form-search">
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

{{if $jotnets}}
{{$jotnets nofilter}}
{{/if}}{{/if}}

<script type="text/javascript">
$(document).ready(function() {
	if(typeof acl=="undefined"){
		acl = new ACL(
			baseurl+"/acl",
			[ {{$allowcid nofilter}},{{$allowgid nofilter}},{{$denycid nofilter}},{{$denygid nofilter}} ],
			{{$features.aclautomention}},
			{{if $APP->is_mobile}}true{{else}}false{{/if}}
		);
	}
});
</script>
