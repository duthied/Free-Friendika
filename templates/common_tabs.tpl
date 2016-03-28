
<ul class="tabs visible-lg visible-md visible-sm hidden-xs" role="menubar" > <!- for Computer & Tablets -->
	{{foreach $tabs as $tab}}
		{{$count=$count+1}}
		{{if $count <= 4}}
		<li id="{{$tab.id}}" role="presentation" {{if $tab.sel}} class="{{$tab.sel}}" {{/if}}><a href="{{$tab.url}}" {{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a></li>
		{{else}}
		{{$exttabs[]=$tab}}
		{{/if}}
	{{/foreach}}


	{{if $count > 5}}
	<li class="dropdown pull-right">
		<a class="dropdown-toggle" type="button" id="dropdownMenuTools" data-toggle="dropdown" aria-expanded="true">
			<i class="fa fa-chevron-down"></i>
		</a>
		<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="dropdownMenuTools">
			{{foreach $exttabs as $tab}}
				<li id="{{$tab.id}}" role="presentation" {{if $tab.sel}} class="{{$tab.sel}}" {{/if}}><a href="{{$tab.url}}" {{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a></li>
			{{/foreach}}
		</ul>
	</li>
	{{/if}}

</ul>

<ul class="tabs visible-xs" role="menubar">
	{{foreach $tabs as $tab}}
		{{if $tab.sel}}
		<li id="{{$tab.id}}" role="presentation" {{if $tab.sel}} class="{{$tab.sel}}" {{/if}}><a href="{{$tab.url}}" {{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a></li>
		{{else}}
		{{$exttabs[]=$tab}}
		{{/if}}
	{{/foreach}}

	<li class="dropdown">
		<a class="dropdown-toggle" type="button" id="dropdownMenuTools" data-toggle="dropdown" aria-expanded="true">
			<i class="fa fa-chevron-down"></i>
		</a>
		<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="dropdownMenuTools">
			{{foreach $exttabs as $tab}}
			<li id="{{$tab.id}}" role="presentation" {{if $tab.sel}} class="{{$tab.sel}}" {{/if}}><a href="{{$tab.url}}" {{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a></li>
			{{/foreach}}
		</ul>
	</li>
</ul>
