
{{* This is the standard template for showing contact lists. It is used e.g. 
at the suggest page and also at many other places *}}

{{include file="section_title.tpl"}}

{{$tab_str}}

<ul id="viewcontact_wrapper{{if $id}}-{{$id}}{{/if}}" class="viewcontact_wrapper media-list">
{{foreach $contacts as $contact}}
	<li>{{include file="contact_template.tpl"}}</li>
{{/foreach}}
</ul>
<div class="clear"></div>
<div id="view-contact-end"></div>

{{$paginate}}
