	
	<div class='field password' id='wrapper_{{$field.0}}'>
		<label for='id_{{$field.0}}'>{{$field.1}}</label>
		<input type='password' name='{{$field.0}}' id='id_{{$field.0}}' value="{{$field.2|escape:'html'}}"{{if $field.4 eq 'required'}} required{{/if}}{{if $field.5 eq 'autofocus'}} autofocus{{/if}}>
		<span class='field_help'>{{$field.3}}</span>
	</div>
