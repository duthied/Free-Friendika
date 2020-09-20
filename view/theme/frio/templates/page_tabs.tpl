<ul class="nav nav-tabs">
{{foreach $tabs as $tab}}
	<li id="{{$tab.id}}" role="presentation"{{if $tab.sel}} class="{{$tab.sel}}"{{/if}}>
		<a role="menuitem" href="{{$tab.url}}"{{if $tab.accesskey}} accesskey="{{$tab.accesskey}}"{{/if}}{{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a>
	</li>
{{/foreach}}
</ul>
