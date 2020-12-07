
<div class="form-group field input file">
	<label for="id_{{$field.0}}" id="label_{{$field.0}}">{{$field.1}}</label>
	<div class="input-group" id="{{$field.0}}">
		<input class="form-control file" name="{{$field.0}}" id="id_{{$field.0}}" type="text" value="{{$field.2 nofilter}}" aria-describedby="{{$field.0}}_tip">
		{{if $field.4}}<span class="required">{{$field.4}}</span>{{/if}}
		<span class="input-group-addon image-select"><i class="fa fa-picture-o"></i></span>
	</div>
	{{if $field.3}}
	<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3 nofilter}}</span>
	{{/if}}
	<div id="end_{{$field.0}}" class="field_end"></div>
</div>
