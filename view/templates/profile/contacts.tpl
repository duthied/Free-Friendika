<div class="generic-page-wrapper">
	{{include file="section_title.tpl"}}

	<ul role="menubar" class="tabs">
		<li role="menuitem"><a href="profile/{{$nickname}}/contacts" class="tab button{{if !$type || $type == 'all'}} active{{/if}}">{{$all_label}}</a></li>
		<li role="menuitem"><a href="profile/{{$nickname}}/contacts/followers" class="tab button{{if $type == 'followers'}} active{{/if}}">{{$followers_label}}</a></li>
		<li role="menuitem"><a href="profile/{{$nickname}}/contacts/following" class="tab button{{if $type == 'following'}} active{{/if}}">{{$following_label}}</a></li>
		<li role="menuitem"><a href="profile/{{$nickname}}/contacts/mutuals" class="tab button{{if $type == 'mutuals'}} active{{/if}}">{{$mutuals_label}}</a></li>
	</ul>

	<div id="viewcontact_wrapper-{{$id}}">
{{foreach $contacts as $contact}}
		{{include file="contact_template.tpl"}}
{{/foreach}}
	</div>
	<div class="clear"></div>
	<div id="view-contact-end"></div>

	{{$paginate nofilter}}
</div>
