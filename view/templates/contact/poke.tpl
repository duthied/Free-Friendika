<h2 class="heading">{{$title}}</h2>

<p>{{$desc nofilter}}</p>

<form id="poke-wrapper" action="contact/{{$id}}/poke" method="post">
	{{include file="field_select.tpl" field=$verb}}
	{{include file="field_checkbox.tpl" field=$private}}
	<p class="text-right">
		<button type="submit" class="btn btn-primary" name="submit" value="{{$submit}}" data-loading-text="{{$loading}}">{{$submit}}</button>
	</p>
</form>
