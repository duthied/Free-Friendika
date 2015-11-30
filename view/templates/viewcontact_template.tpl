
{{include file="section_title.tpl"}}

<div id="viewcontact_wrapper-{{$id}}">
{{foreach $contacts as $contact}}
	{{include file="contact_template.tpl"}}
{{/foreach}}
</div>

<div id="view-contact-end"></div>

{{$paginate}}
