
{{$live_update nofilter}}

{{foreach $threads as $thread}}
{{include file="{{$thread.template}}" item=$thread}}
{{/foreach}}

<div id="conversation-end"></div>

{{if $dropping}}
<div id="item-delete-selected" class="fakelink" onclick="deleteCheckedItems();">
  <div id="item-delete-selected-icon" class="icon drophide" title="{{$dropping}}" onmouseover="imgbright(this);" onmouseout="imgdull(this);" ></div>
  <div id="item-delete-selected-desc" >{{$dropping}}</div>
</div>
<img id="item-delete-selected-rotator" class="like-rotator" src="images/rotator.gif" style="display: none;" />
<div id="item-delete-selected-end"></div>
{{/if}}
