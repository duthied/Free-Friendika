<div class="generic-page-wrapper">
	<h1>{{$l10n.title}} - {{$l10n.page}}</h1>
	<p>{{$l10n.description}}</p>

	<form action="" method="post">
        {{include file="field_radio.tpl" field=$category_spam}}
        {{include file="field_radio.tpl" field=$category_illegal}}
        {{include file="field_radio.tpl" field=$category_safety}}
        {{include file="field_radio.tpl" field=$category_unwanted}}
        {{include file="field_radio.tpl" field=$category_violation}}
        {{include file="field_radio.tpl" field=$category_other}}

		{{include file="field_textarea.tpl" field=$comment}}
		<p><button type="submit" class="btn btn-primary">{{$l10n.submit}}</button></p>
	</form>
</div>
