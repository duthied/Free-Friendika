<h4>{{$account_types}}</h4>
{{include file="field_radio.tpl" field=$account_person}}
{{include file="field_radio.tpl" field=$account_company}}
{{include file="field_radio.tpl" field=$account_news}}
{{include file="field_radio.tpl" field=$account_community}}

{{if $account_type == $type_person}}
	<h5>{{$user}}</h5>
	{{include file="field_radio.tpl" field=$page_normal}}
	{{include file="field_radio.tpl" field=$page_soapbox}}
	{{include file="field_radio.tpl" field=$page_freelove}}
{{/if}}

{{if $account_type == $type_company}}
	<input type='hidden' name='page-flags' value='1'>
{{/if}}

{{if $account_type == $type_news}}
	<input type='hidden' name='page-flags' value='1'>
{{/if}}

{{if $account_type == $type_community}}
	<h5>{{$community}}</h5>
	{{include file="field_radio.tpl" field=$page_community}}
	{{include file="field_radio.tpl" field=$page_prvgroup}}
{{/if}}
