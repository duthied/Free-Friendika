<div class="generic-page-wrapper">
	<h1>{{$l10n.title}} - {{$l10n.page}}</h1>
	<p>{{$l10n.description}}</p>

	<form action="" method="post">
	{{foreach $rules as $rule}}
		<div class="field checkbox" id="div_id_{{$rule.0}}_{{$rule.1}}">
			<input type="checkbox" name="{{$rule.0}}" id="id_{{$rule.0}}_{{$rule.1}}" value="{{$rule.1}}" {{if $rule.3}}checked="checked"{{/if}}>
			<label for="id_{{$rule.0}}_{{$rule.1}}">
                {{$rule.2}}
			</label>
		</div>
	{{/foreach}}
		<p><button type="submit" class="btn btn-primary">{{$l10n.submit}}</button></p>
	</form>
</div>
