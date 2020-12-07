
	<div id="id_{{$field.0}}_wrapper" class="form-group field input">
	{{if !isset($label) || $label != false }}
		<label for="id_{{$field.0}}" id="label_{{$field.0}}">{{$field.1 nofilter}}{{if $field.4}}<span class="required"> {{$field.4}}</span>{{/if}}</label>
	{{/if}}
		<input class="form-control" name="{{$field.0}}" id="id_{{$field.0}}"{{if $field.6}} type="{{$field.6}}"{{else}} type="text"{{/if}} value="{{$field.2 nofilter}}"{{if $field.4 eq "required"}} required{{/if}}{{if $field.5 eq "autofocus"}} autofocus{{elseif $field.5}} {{$field.5 nofilter}}{{/if}} aria-describedby="{{$field.0}}_tip">
	{{if $field.3}}
		<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3 nofilter}}</span>
	{{/if}}
		<div class="clear"></div>
	</div>
