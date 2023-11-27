
	<div class="field select">
		<label for="id_{{$field.0}}">{{$field.1}}</label>
		<select name="{{$field.0}}" id="id_{{$field.0}}" aria-describedby="{{$field.0}}_tip" {{$field.5 nofilter}}>
	{{foreach $field.4 as $opt => $val}}
		{{if $field.5 == 'multiple'}}
			<option value="{{$opt}}" dir="auto"{{if in_array($opt, $field.2)}} selected="selected"{{/if}}>{{$val}}</option>
		{{else}}
			<option value="{{$opt}}" dir="auto"{{if $opt == $field.2}} selected="selected"{{/if}}>{{$val}}</option>
		{{/if}}
	{{/foreach}}
		</select>
	{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3 nofilter}}</span>
	{{/if}}
	</div>
