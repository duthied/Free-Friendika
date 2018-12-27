
<div id="nets-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<div id="nets-desc">{{$desc nofilter}}</div>
	<a href="{{$base}}" class="nets-link{{if $sel_all}} nets-selected{{/if}} nets-all">{{$all}}</a>
	<ul role="menu" class="nets-ul">
	{{foreach $nets as $net}}
	<li role="menuitem" ><a href="{{$base}}?nets={{$net.ref}}" class="nets-link{{if $net.selected}} nets-selected{{/if}}">{{$net.name}}</a></li>
	{{/foreach}}
	</ul>
</div>
