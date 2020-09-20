	<div class="form-group field textarea">
	{{if $field.1}}
		<label for="id_{{$field.0}}">{{$field.1}}</label>
	{{/if}}
		<textarea class="form-control text-autosize" name="{{$field.0}}" id="id_{{$field.0}}" {{if $field.4}}{{$field.4 nofilter}}{{/if}} aria-describedby="{{$field.0}}_tip">{{$field.2}}</textarea>
	{{if $field.3}}
		<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3 nofilter}}</span>
	{{/if}}
		<div class="clear"></div>
	</div>
