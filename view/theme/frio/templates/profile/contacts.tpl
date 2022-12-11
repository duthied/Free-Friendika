<div class="generic-page-wrapper">
	{{include file="section_title.tpl"}}

{{if $desc}}
	<p>{{$desc nofilter}}</p>
{{/if}}

	{{include file="page_tabs.tpl" tabs=$tabs}}

{{if $contacts}}
	<ul id="viewcontact_wrapper{{if $id}}-{{$id}}{{/if}}" class="viewcontact_wrapper media-list">
	{{foreach $contacts as $contact}}
		<li>{{include file="contact/entry.tpl"}}</li>
	{{/foreach}}
	</ul>
{{else}}
	<div class="alert alert-info" role="alert">{{$noresult_label}}</div>
{{/if}}
	<div class="clear"></div>
	<div id="view-contact-end"></div>

	{{$paginate nofilter}}
</div>
