
	<div class="field textarea">
		<label for="id_{{$field.0}}">{{$field.1}}{{if $field.4}} <span class="required" title="{{$field.4}}">*</span>{{/if}}</label>
		<textarea name="{{$field.0}}" id="id_{{$field.0}}"{{if $field.4}} required{{/if}} aria-describedby="{{$field.0}}_tip">{{$field.2}}</textarea>
	{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3 nofilter}}</span>
	{{/if}}
	</div>
