
<statuses type="array"
	xmlns:statusnet="http://status.net/schema/api/1/"
	xmlns:friendica="http://friendi.ca/schema/api/1/">
{{foreach $statuses as $status}}
 <status>
	{{include file="api_single_status_xml.tpl" status=$status}}
 </status>
{{/foreach}}
</statuses>
