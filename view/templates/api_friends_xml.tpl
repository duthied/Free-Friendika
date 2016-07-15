{{* used in include/api.php 'api_statuses_friends' and 'api_statuses_followers' *}}
<users type="array">
	{{foreach $users as $u}}
	<user>{{include file="api_user_xml.tpl" user=$u}}</user>
	{{/foreach}}
</users>
