	
	<div class="field input" id="wrapper_{{$field.0}}">
		<label for="id_{{$field.0}}">{{$field.1}}</label>
		<input{{if $field.6}} type="{{$field.6}}"{{else}} type="text"{{/if}} name="{{$field.0}}" id="id_{{$field.0}}" value="{{$field.2 nofilter}}"{{if $field.4 eq 'required'}} required{{/if}}{{if $field.5 eq "autofocus"}} autofocus{{elseif $field.5}} {{$field.5 nofilter}}{{/if}} aria-describedby="{{$field.0}}_tip">
	{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3 nofilter}}</span>
	{{/if}}
	</div>
