<div id="id_{{$field.0}}_wrapper" class="form-group field range">
{{if !isset($label) || $label != false }}
	<label for="{{$field.0}}_range" id="label_{{$field.0}}">{{$field.1}}{{if $field.4}} <span class="required" title="{{$field.4}}">*</span>{{/if}}</label>
{{/if}}
	<div class="row">
		<div class="col-xs-9">
			<input type="range" class="form-control" id="{{$field.0}}_range" min="0" max="100" step="1" value="{{$field.2}}" onchange="{{$field.0}}.value = this.value" oninput="{{$field.0}}.value = this.value">
		</div>
		<div class="col-xs-3">
			<div class="input-group">
				<input type="text" class="form-control input-sm" name="{{$field.0}}" id="{{$field.0}}" value="{{$field.2}}"{{if $field.4}} required{{/if}} onchange="{{$field.0}}_range.value = this.value" oninput="{{$field.0}}_range.value = this.value" aria-describedby="{{$field.0}}_tip">
				<span class="input-group-addon image-select">%</span>
			</div>
		</div>
	</div>
{{if $field.3}}
	<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3 nofilter}}</span>
{{/if}}
	<div class="clear"></div>
</div>
