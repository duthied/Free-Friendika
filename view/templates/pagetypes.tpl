{{if $page_type == $page_person}}
<h4>{{$user}}</h4>
	{{include file="field_radio.tpl" field=$page_normal}}
	{{include file="field_radio.tpl" field=$page_soapbox}}
	{{include file="field_radio.tpl" field=$page_freelove}}
{{/if}}
{{if $page_type == $page_company}}
<h4>{{$company}}</h4>
	{{include file="field_radio.tpl" field=$page_soapbox}}
{{/if}}
{{if $page_type == $page_forum}}
<h4>{{$community}}</h4>
	{{include file="field_radio.tpl" field=$page_community}}
	{{include file="field_radio.tpl" field=$page_prvgroup}}
{{/if}}
