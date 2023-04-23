
{{include file="section_title.tpl"}}

{{$tab_str nofilter}}

<div id="viewcontact_wrapper-{{$id}}">
{{foreach $contacts as $contact}}
	{{include file="contact/entry.tpl"}}
{{/foreach}}
</div>
<div class="clear"></div>
<div id="view-contact-end"></div>

{{$paginate nofilter}}

{{if $filtered}}
	<p>{{$filtered nofilter}}</p>
{{/if}}
