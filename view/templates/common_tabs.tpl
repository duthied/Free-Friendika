
<ul role="menubar" class="tabs">
	{{foreach $tabs as $tab}}
		<li role="menuitem" id="{{$tab.id}}"><a href="{{$tab.url}}" {{if $tab.accesskey}}accesskey="{{$tab.accesskey}}"{{/if}} class="tab button {{$tab.sel}}"{{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a></li>
	{{/foreach}}
</ul>
