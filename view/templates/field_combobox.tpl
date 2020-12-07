	
	<div class='field combobox'>
		<label for='id_{{$field.0}}' id='id_{{$field.0}}_label'>{{$field.1}}</label>
		{{* html5 don't work on Chrome, Safari and IE9
		<input id="id_{{$field.0}}" type="text" list="data_{{$field.0}}" >
		<datalist id="data_{{$field.0}}" >
		   {{foreach $field.4 as $opt=>$val}}<option value="{{$val}}">{{/foreach}}
		</datalist> *}}
		
		<input id="id_{{$field.0}}" type="text" value="{{$field.2 nofilter}}" aria-describedby='{{$field.0}}_tip'>
		<select id="select_{{$field.0}}" onChange="$('#id_{{$field.0}}').val($(this).val())">
			<option value="">{{$field.5}}</option>
			{{foreach $field.4 as $opt=>$val}}<option value="{{$val}}">{{$val}}</option>{{/foreach}}
		</select>
		
		{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3 nofilter}}</span>
		{{/if}}
	</div>

