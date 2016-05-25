
<div id="wrapper_{{$field.0}}" class="form-group field password">
	<label for="id_{{$field.0}}">{{$field.1}}</label>
	<input class="form-control" type="password" name="{{$field.0}}" id="id_{{$field.0}}" value="{{$field.2|escape:'html'}}"{{if $field.5}} {{$field.5}}{{/if}}>{{if $field.4}} <span class="required">{{$field.4}}</span> {{/if}}
	<span id="{{$field.0}}_tip" class="help-block" role="tooltip">{{$field.3}}</span>
</div>
