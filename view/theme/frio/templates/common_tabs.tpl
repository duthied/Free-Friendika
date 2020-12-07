
<div class="tabbar-wrapper">
	{{* Tab navigation bar for tablets and computer *}}
	<ul role="menubar" class="tabbar list-inline visible-lg visible-md visible-sm hidden-xs">
		{{* The normal tabbar *}}
		<li>
			<ul class="tabs  flex-nav" role="menu" >
			{{foreach $tabs as $tab}}
				<li id="{{$tab.id}}" role="presentation" {{if $tab.sel}} class="{{$tab.sel}}" {{/if}}><a role="menuitem" href="{{$tab.url}}" {{if $tab.accesskey}}accesskey="{{$tab.accesskey}}"{{/if}} {{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a></li>
			{{/foreach}}
			</ul>
		</li>

		{{* The extended dropdown menu - this would be shown if the tab menu points
			doesn't fit in the available space. This is done through flexMenu.js *}}
		<li class="pull-right">
			<ul class="tabs tabs-extended" role="menu">
				<li role="presentation" class="dropdown flex-target">
					<button type="button" class="btn-link dropdown-toggle" id="dropdownMenuTools" data-toggle="dropdown" aria-expanded="false">
						<i class="fa fa-chevron-down" aria-hidden="true"></i>
					</button>
				</li>
			 </ul>
		</li>
	</ul>

	{{* Tab navigation bar for smartphones *}}
	<ul role="menubar" class="tabbar list-inline visible-xs">
		{{* The active menupoint will be shown as one menupoint*}}
		<li>
			<ul class="tabs" role="menu">
				{{foreach $tabs as $tab}}
					{{if $tab.sel}}
					<li id="{{$tab.id}}-xs" role="presentation" {{if $tab.sel}} class="{{$tab.sel}}" {{/if}}><a role="menuitem" href="{{$tab.url}}" {{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a></li>
					{{else}}
					{{$exttabs[]=$tab}}
					{{/if}}
				{{/foreach}}


			</ul>
		</li>

		{{* All others are moved to this dropdown menu *}}
		<li class="pull-right">
			<ul class="tabs tabs-extended">
				<li class="dropdown">
					<button type="button" class="btn-link dropdown-toggle" id="dropdownMenuTools-xs" data-toggle="dropdown" aria-expanded="false">
						<i class="fa fa-chevron-down" aria-hidden="true"></i>
					</button>
					<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="dropdownMenuTools">
						{{foreach $exttabs as $tab}}
						<li id="{{$tab.id}}-xs" role="presentation" {{if $tab.sel}} class="{{$tab.sel}}" {{/if}}><a role="menuitem" href="{{$tab.url}}" {{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a></li>
						{{/foreach}}
					</ul>
				</li>
			</ul>
		</li>
	</ul>
</div>
