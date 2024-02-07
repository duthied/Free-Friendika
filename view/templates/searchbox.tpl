<div id="{{$id}}" class="input-group">
	<form action="search" method="get">
{{strip}}
		<input type="text" name="q" id="search-text" placeholder="{{$search_label}}" value="{{$s}}">
    {{if $search_options}}
		<select name="search-option" id="search-options">
		{{foreach $search_options as $value => $label}}
			<option value="{{$value}}">{{$label}}</option>
		{{/foreach}}
		</select>
    {{/if}}
		<input type="submit" name="submit" id="search-submit" value="{{$search_label}}"/>
    {{if $s}}
	    <a href="search/saved/add?term={{$q}}&amp;return_url={{$return_url}}">{{$save_label}}</a>
    {{/if}}
{{/strip}}
	</form>
</div>
