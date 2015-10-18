
{{include file="section_title.tpl"}}

<div id="contacts-display-wrapper">
{{foreach $contacts as $contact}}
	{{include file="contact_template.tpl"}}
{{/foreach}}
</div>

<div id="view-contact-end"></div>

{{$paginate}}
