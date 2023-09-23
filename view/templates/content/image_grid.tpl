{{foreach $rows as $images}}
	<div class="imagegrid-row" style="height: {{$images[0].commonHeightRatio}}%">
	{{foreach $images as $image}}
		{{* The absolute pixel value in the calc() should be mirrored from the .imagegrid-row column-gap value *}}
		<figure style="width: {{$image.widthRatio}}%; padding-bottom: calc({{$image.heightRatio * $image.widthRatio / 100}}% - 5px / 2)">
            {{include file="content/image.tpl" image=$image}}
		</figure>
    {{/foreach}}
	</div>
{{/foreach}}
