<div class="mail-list-outside-wrapper">
	

		
	<div class="media">
		<div class="media-left">
			<a href="{{$from_url}}">
				<img class="media-object" src="{{$from_photo}}" alt="{{$from_name}}" style="min-width:80px; min-height:80px; width:80px; height:80px; max-width:80px; max-height:80px;" />
			</a>
		</div>
		<div class="media-body">
			<div class="text-muted time ago pull-right" title="{{$date}}">{{$ago}}</div>
			
			<h5 class="media-heading">{{$from_name}}</h5>
			<a href="message/{{$id}}">
				<h4>{{$subject}}</h4>
			</a>
			<a href="message/dropconv/{{$id}}" onclick="return confirmDelete();"  title="{{$delete}}" class="close pull-right" onmouseover="imgbright(this);" onmouseout="imgdull(this);" >&times;</a>
			<p class="text-muted">{{$count}}</p>
		</div>

	</div>


	
</div>
<div class="mail-list-delete-end"></div>

<div class="mail-list-outside-wrapper-end"></div>

