
<div id="mail-thread-{{$thread_id}}" class="mail-thread">
	{{$brauchenwas}}
	<h4 class="heading">{{$thread_subject}}</h3>

	<div id="mail-conversation" class="panel panel-default {{if $canreply }}can-reply{{/if}}">
	{{foreach $mails as $mail}}
		{{include file="mail_conv.tpl"}}
	{{/foreach}}
	</div>

	<div id="mail-reply">
	{{if $canreply}}
		{{include file="prv_message.tpl"}}
	{{else}}
		{{$unknown_text}}
	{{/if}}
	</div>
</div>
