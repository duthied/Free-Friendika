<div id="id_{{$field.0}}_wrapper" class="form-group field input password">
	<label for="id_{{$field.0}}" id="label_{{$field.0}}">{{$field.1}}{{if $field.4}} <span class="required" title="{{$field.4}}">*</span>{{/if}}</label>
	<input class="form-control" name="{{$field.0}}" id="id_{{$field.0}}" type="password" value="{{$field.2}}" {{if $field.4}}required{{/if}} {{$field.5 nofilter}} {{if $field.6}}pattern="{{$field.6}}"{{/if}} aria-describedby="{{$field.0}}_tip">
	{{if $field.3}}
	<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3 nofilter}}</span>
	{{/if}}
	<div class="clear"></div>
</div>
