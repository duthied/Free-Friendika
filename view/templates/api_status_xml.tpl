{{* used in api.php to return a single status *}}
<status
	xmlns:statusnet="http://status.net/schema/api/1/"
	xmlns:friendica="http://friendi.ca/schema/api/1/">
	{{if $status}}
	{{include file="api_single_status_xml.tpl" status=$status}}
	{{/if}}
</status>
