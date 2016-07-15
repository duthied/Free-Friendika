{{* shared structure for statuses. includers must define root element *}}
  <text>{{$status.text}}</text>
  <truncated>{{$status.truncated}}</truncated>
  <created_at>{{$status.created_at}}</created_at>
  <in_reply_to_status_id>{{$status.in_reply_to_status_id}}</in_reply_to_status_id>
  <source>{{$status.source}}</source>
  <id>{{$status.id}}</id>
  <in_reply_to_user_id>{{$status.in_reply_to_user_id}}</in_reply_to_user_id>
  <in_reply_to_screen_name>{{$status.in_reply_to_screen_name}}</in_reply_to_screen_name>
  <geo>{{$status.geo}}</geo>
  <favorited>{{$status.favorited}}</favorited>
	<user>{{include file="api_user_xml.tpl" user=$status.user}}</user>
	<friendica:owner>{{include file="api_user_xml.tpl" user=$status.friendica_owner}}</friendica:owner>
  <statusnet:html>{{$status.statusnet_html}}</statusnet:html>
  <statusnet:conversation_id>{{$status.statusnet_conversation_id}}</statusnet:conversation_id>
  <url>{{$status.url}}</url>
  <coordinates>{{$status.coordinates}}</coordinates>
  <place>{{$status.place}}</place>
  <contributors>{{$status.contributors}}</contributors>
  {{if $status.retweeted_status}}<retweeted_status>{{include file="api_single_status_xml.tpl" status=$status.retweeted_status}}</retweeted_status>{{/if}}
  <friendica:activities>
    {{foreach $status.friendica_activities as $k=>$v}}
    <friendica:{{$k}}>{{$v|count}}</friendica:{{$k}}>
    {{/foreach}}
  </friendica:activities>