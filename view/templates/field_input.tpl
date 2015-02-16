	
	<div class='field input' id='wrapper_{{$field.0}}'>
		<label for='id_{{$field.0}}'>{{$field.1}}</label>
		<input{{if $field.6 eq 'email'}} type='email'{{elseif $field.6 eq 'url'}} type='url'{{/if}} name='{{$field.0}}' id='id_{{$field.0}}' value="{{$field.2|escape:'html'}}"{{if $field.4 eq 'required'}} required{{/if}}{{if $field.5 eq 'autofocus'}} autofocus{{/if}}>
		<span class='field_help'>{{$field.3}}</span>
	</div>
