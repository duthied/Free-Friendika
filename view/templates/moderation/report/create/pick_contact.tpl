<div class="generic-page-wrapper">
	<h1>{{$l10n.title}} - {{$l10n.page}}</h1>
	<p>{{$l10n.description}}</p>

	<form action="" method="post">
		{{include file="field_input.tpl" field=$url}}
		<p><button type="submit" class="btn btn-primary">{{$l10n.submit}}</button></p>
	</form>
</div>
