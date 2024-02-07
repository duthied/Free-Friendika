<div class="imagegrid-row">
	<div class="imagegrid-column">
		{{foreach $columns.fc as $img}}
				{{include file="content/image/single.tpl" image=$img}}
		{{/foreach}}
	</div>
	<div class="imagegrid-column">
		{{foreach $columns.sc as $img}}
				{{include file="content/image/single.tpl" image=$img}}
		{{/foreach}}
	</div>
</div>
