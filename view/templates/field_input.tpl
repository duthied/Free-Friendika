	
	<div class="field input" id="wrapper_{{$field.0}}">
		<label for="id_{{$field.0}}">{{$field.1}}{{if $field.4}} <span class="required" title="{{$field.4}}">*</span>{{/if}}</label>
		<input type="{{$field.6|default:'text'}}" name="{{$field.0}}" id="id_{{$field.0}}" value="{{$field.2}}"{{if $field.4}} required{{/if}}{{if $field.5 eq "autofocus"}} autofocus{{elseif $field.5}} {{$field.5 nofilter}}{{/if}} aria-describedby="{{$field.0}}_tip">
	{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3 nofilter}}</span>
	{{/if}}
	</div>
