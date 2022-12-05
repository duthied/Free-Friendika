<link rel="stylesheet" href="view/theme/frio/css/image_grid.css" type="text/css" media="screen"/>

<div id="row" class="row">
	<div class="column">
        {{foreach $columns.fc as $fc}}
        	{{foreach $fc as $img}}
	        	{{include file="content/image.tpl" image=$img}}
			{{/foreach}}
		{{/foreach}}
	</div>
	<div class="column">
        {{foreach $columns.sc as $sc}}
			{{foreach $sc as $img}}
				{{include file="content/image.tpl" image=$img}}
			{{/foreach}}
		{{/foreach}}
	</div>
</div>