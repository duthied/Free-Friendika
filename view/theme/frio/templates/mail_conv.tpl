<div id="mail_conv-{{$mail.id}}" class="mail-conv-wrapper message-{{$mail.id}}">
	<div class="media">
		<div class="pull-left contact-photo-wrapper">
			<a href="{{$mail.from_url}}">
				<img class="media-object" src="{{$mail.from_photo}}" alt="{{$mail.from_name}}" />
			</a>
		</div>
		<div class="media-body">
			<div class="text-muted time mail-ago pull-right" title="{{$mail.date}}" data-toggle="tooltip">{{$mail.date}}</div>
			<div class="mail-conv-delete-end"></div>
			<h4 class="media-heading"><a href="{{$mail.from_url}}">{{$mail.from_name}}</a></h4>

			<div class="mail-body">
				{{$mail.body}}
			</div>
			{{*<a href="message/dropconv/{{$mail.id}}" onclick="return confirmDelete();" title="{{$delete}}" class="close pull-right" onmouseover="imgbright(this);" onmouseout="imgdull(this);" >&times;</a> *}}
		</div>
	</div>
	<div class="mail-conv-wrapper-end"></div>
</div>

