
{{include file="section_title.tpl"}}

{{$tab_str nofilter}}

<div id="viewcontact_wrapper-{{$id}}">
{{foreach $contacts as $contact}}
	{{include file="contact_template.tpl"}}
{{/foreach}}
</div>
<div class="clear"></div>
<div id="view-contact-end"></div>

{{$paginate nofilter}}
