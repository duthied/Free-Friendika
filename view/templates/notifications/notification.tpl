
<div class="notif-item {{if !$item_seen}}unseen{{/if}}" {{if $item_seen}}aria-hidden="true"{{/if}}>
	<a href="{{$item_link}}"><img src="{{$item_image}}" aria-hidden="true" class="notif-image">{{$item_text nofilter}} <span class="notif-when">{{$item_ago}}</span></a>
</div>
