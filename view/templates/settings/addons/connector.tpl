<span id="settings_{{$connector}}_inflated" class="settings-block fakelink" style="display: {{if $open}}none{{else}}block{{/if}};" onclick="openClose('settings_{{$connector}}_expanded'); openClose('settings_{{$connector}}_inflated');">
	<img class="connector{{if !$enabled}}-disabled{{/if}}" src="{{$image}}" /><h3 class="connector">{{$title}}</h3>
</span>
<div id="settings_{{$connector}}_expanded" class="settings-block" style="display: {{if $open}}block{{else}}none{{/if}};">
	<span class="fakelink" onclick="openClose('settings_{{$connector}}_expanded'); openClose('settings_{{$connector}}_inflated');">
		<img class="connector{{if !$enabled}}-disabled{{/if}}" src="{{$image}}" /><h3 class="connector">{{$title}}</h3>
	</span>
	{{$html nofilter}}
	<div class="clear"></div>
{{if $submit}}
	<div class="settings-submit-wrapper panel-footer">
    {{if $submit|is_string}}
		<button type="submit" name="{{$connector}}-submit" class="btn btn-primary settings-submit" value="{{$submit}}">{{$submit}}</button>
    {{else}}
        {{$count = 1}}
		{{foreach $submit as $name => $label}}{{if $label}}
			{{if $count == 1}}
		<button type="submit" name="{{$name}}" class="btn btn-primary settings-submit" value="{{$label}}">{{$label}}</button>
            {{/if}}
            {{if $count == 2}}
		<div class="btn-group" role="group" aria-label="...">
			{{/if}}
            {{if $count != 1}}
			<button type="submit" name="{{$name}}" class="btn btn-default settings-submit" value="{{$label}}">{{$label}}</button>
            {{/if}}
            {{$count = $count + 1}}
        {{/if}}{{/foreach}}
		{{if $submit|count > 1}}
		</div>
		{{/if}}
    {{/if}}
	</div>
{{/if}}
</div>
