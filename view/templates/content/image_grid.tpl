<link rel="stylesheet" href="view/theme/frio/css/image_grid.css" type="text/css" media="screen"/>

<div id="row" class="row">
	<div class="column">
        {{foreach $columns.fc as $img}}
        	{{include file="content/image.tpl" image=$img}}
		{{/foreach}}
	</div>
	<div class="column">
        {{foreach $columns.sc as $img}}
    	    {{include file="content/image.tpl" image=$img}}
		{{/foreach}}
	</div>
</div>