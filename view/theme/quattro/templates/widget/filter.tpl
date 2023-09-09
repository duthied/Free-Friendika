<div id="{{$type}}-sidebar" class="widget">
	<h3>{{$title}}</h3>
{{if $desc}}
	<div id="{{$type}}-desc">{{$desc nofilter}}</div>
{{/if}}
	
	<ul class="{{$type}}-ul">
		{{if $all_label}}
		<li class="tool {{if !$selected}}selected{{/if}}"><a href="{{$base}}" class="{{$type}}-link {{$type}}-all">{{$all_label}}</a>
		{{/if}}
{{foreach $options as $option}}
		<li class="tool {{if $selected == $option.ref}}selected{{/if}}"><a href="{{$base}}{{$type}}={{$option.ref}}" class="{{$type}}-link">{{$option.name}}</a></li>
{{/foreach}}
	</ul>
	
</div>
