{{* This is the standard template for showing contact lists. It is used e.g.
at the suggest page and also at many other places *}}
<div class="generic-page-wrapper">
	{{include file="section_title.tpl"}}

	{{$tab_str nofilter}}

	<ul id="viewcontact_wrapper{{if $id}}-{{$id}}{{/if}}" class="viewcontact_wrapper media-list">
{{foreach $contacts as $contact}}
		<li>{{include file="contact/entry.tpl"}}</li>
{{/foreach}}
	</ul>
	<div class="clear"></div>
	<div id="view-contact-end"></div>

	{{$paginate nofilter}}

{{if $filtered}}
	<p>{{$filtered nofilter}}</p>
{{/if}}
</div>
