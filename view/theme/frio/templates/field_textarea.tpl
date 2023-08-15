	<div class="form-group field textarea">
	{{if $field.1}}
		<label for="id_{{$field.0}}">{{$field.1}}{{if $field.4}} <span class="required" title="{{$field.4}}">*</span>{{/if}}</label>
	{{/if}}
		<textarea class="form-control text-autosize" name="{{$field.0}}" id="id_{{$field.0}}"{{if $field.4}} required{{/if}}{{if $field.5}} {{$field.5}}{{/if}} aria-describedby="{{$field.0}}_tip">{{$field.2}}</textarea>
	{{if $field.3}}
		<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3 nofilter}}</span>
	{{/if}}
		<div class="clear"></div>
	</div>
