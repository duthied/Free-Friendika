	<div class="field checkbox" id="div_id_{{$field.0}}">
		<label id="id_{{$field.0}}_label" for="id_{{$field.0}}">{{$field.1}}</label>
		<input type="hidden" name="{{$field.0}}" value="0">
		<input type="checkbox" name="{{$field.0}}" id="id_{{$field.0}}" aria-describedby="{{$field.0}}_tip" value="1" {{if $field.2}}checked="checked"{{/if}} {{if $field.4}}{{$field.4}}{{/if}}>
		{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3 nofilter}}</span>
		{{/if}}
	</div>
