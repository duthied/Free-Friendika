
<div id="nets-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<div id="nets-desc">{{$desc}}</div>
	<ul role="menu" class="nets-ul">
		<li role="menuitem" {{if $sel_all}}class="selected"{{/if}}><a href="{{$base}}?nets=all" class="nets-link{{if $sel_all}} nets-selected{{/if}} nets-all">{{$all}}</a></li>
		{{foreach $nets as $net}}
		<li role="menuitem" {{if $net.selected}}class="selected"{{/if}}><a href="{{$base}}?nets={{$net.ref}}" class="nets-link{{if $net.selected}} nets-selected{{/if}}">{{$net.name}}</a></li>
		{{/foreach}}
	</ul>
</div>
