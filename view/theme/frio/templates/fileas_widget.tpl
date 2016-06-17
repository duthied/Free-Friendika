
<div id="fileas-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<div id="nets-desc">{{$desc}}</div>
	
	<ul class="fileas-ul">
		<li class="tool{{if $sel_all}} selected{{/if}}"><a href="{{$base}}" class="fileas-link fileas-all{{if $sel_all}} fileas-selected{{/if}}">{{$all}}</a></li>
		{{foreach $terms as $term}}
			<li class="tool{{if $term.selected}} selected{{/if}}"><a href="{{$base}}?f=&file={{$term.name}}" class="fileas-link{{if $term.selected}} fileas-selected{{/if}}">{{$term.name}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
